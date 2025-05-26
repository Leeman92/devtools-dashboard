<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:list-users',
    description: 'List all users in the system',
)]
class ListUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->entityManager->getRepository(User::class)->findAll();

        if (empty($users)) {
            $io->info('No users found in the system.');
            return Command::SUCCESS;
        }

        $tableData = [];
        foreach ($users as $user) {
            $tableData[] = [
                $user->getId(),
                $user->getEmail(),
                $user->getName(),
                implode(', ', $user->getRoles()),
                $user->getCreatedAt()?->format('Y-m-d H:i:s'),
                $user->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        $io->title('Dashboard Users');
        $io->table(
            ['ID', 'Email', 'Name', 'Roles', 'Created', 'Updated'],
            $tableData
        );

        $io->success(sprintf('Found %d user(s) in the system.', count($users)));

        return Command::SUCCESS;
    }
} 