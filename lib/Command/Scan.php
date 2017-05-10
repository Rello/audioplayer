<?php
/**
 * Audio Player
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2017 Marcel Scherello
 */
 
namespace OCA\audioplayer\Command;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use OCA\audioplayer\Controller;

class Scan extends Command {
	private $userManager;
	private $scanner;
	public function __construct(
			\OCP\IUserManager $userManager, 
			$scanner
		) {
		$this->userManager = $userManager;
		$this->scanner = $scanner;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('audioplayer:scan')
			->setDescription('scan for new audio files')
			->addArgument(
					'user_id',
					InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
					'scan all audio files of the given user(s)'
			)
			->addOption(
					'all',
					null,
					InputOption::VALUE_NONE,
					'scan all audio files of all known users'
			)
			->addOption(
					'debug',
					null,
					InputOption::VALUE_NONE,
					'current processed audio file will be written for detailed analysis'
			)
		;
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('all')) {
			$users = $this->userManager->search('');
		} else {
			$users = $input->getArgument('user_id');
		}

		if (count($users) === 0) {
			$output->writeln("<error>Please specify a valid user id to scan, \"--all\" to scan for all users<error>");
			return;
		}
		
		foreach ($users as $userId) {
			if (is_object($userId)) $user = $userId;
			else $user = $this->userManager->get($userId);

			if ($user === null) {
				$output->writeln("<error>User $userId does not exist</error>");
			} else {
				$userId = $user->getUID();
				$output->writeln("<info>Start scan for $userId</info>");
				$this->scanner->scanForAudios($userId, $output, $input->getOption('debug'));
			}
		}
	}
}
