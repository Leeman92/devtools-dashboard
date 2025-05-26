<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create a new user for the dashboard application',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email address')
            ->addArgument('name', InputArgument::REQUIRED, 'User full name')
            ->addOption('admin', 'a', InputOption::VALUE_NONE, 'Grant admin privileges')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'User password (will prompt if not provided)')
            ->setHelp('This command allows you to create a new user for the dashboard application.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getArgument('email');
        $name = $input->getArgument('name');
        $isAdmin = $input->getOption('admin');
        $password = $input->getOption('password');

        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser) {
            $io->error(sprintf('User with email "%s" already exists!', $email));
            return Command::FAILURE;
        }

        // Get password securely
        if (!$password) {
            $helper = $this->getHelper('question');
            $question = new Question('Please enter the password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $helper->ask($input, $output, $question);
        }

        if (empty($password)) {
            $io->error('Password cannot be empty!');
            return Command::FAILURE;
        }

        // Validate password strength
        if (strlen($password) < 8) {
            $io->error('Password must be at least 8 characters long!');
            return Command::FAILURE;
        }

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        
        // Set roles
        $roles = ['ROLE_USER'];
        if ($isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }
        $user->setRoles($roles);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Validate user
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $io->error('Validation failed:');
            foreach ($errors as $error) {
                $io->text('- ' . $error->getMessage());
            }
            return Command::FAILURE;
        }

        // Save user
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success(sprintf(
                'User "%s" (%s) created successfully with %s privileges!',
                $name,
                $email,
                $isAdmin ? 'admin' : 'user'
            ));

            $io->table(
                ['Field', 'Value'],
                [
                    ['ID', $user->getId()],
                    ['Email', $user->getEmail()],
                    ['Name', $user->getName()],
                    ['Roles', implode(', ', $user->getRoles())],
                    ['Created', $user->getCreatedAt()?->format('Y-m-d H:i:s')],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to create user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 