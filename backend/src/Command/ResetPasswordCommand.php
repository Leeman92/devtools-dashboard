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

#[AsCommand(
    name: 'app:reset-password',
    description: 'Reset password for an existing user',
)]
class ResetPasswordCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email address')
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'New password (will prompt if not provided)')
            ->setHelp('This command allows you to reset the password for an existing user.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getArgument('email');
        $password = $input->getOption('password');

        // Find user
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('User with email "%s" not found!', $email));
            return Command::FAILURE;
        }

        // Get new password securely
        if (!$password) {
            $helper = $this->getHelper('question');
            $question = new Question('Please enter the new password: ');
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

        // Hash and update password
        try {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $this->entityManager->flush();

            $io->success(sprintf(
                'Password for user "%s" (%s) has been reset successfully!',
                $user->getName(),
                $user->getEmail()
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to reset password: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 