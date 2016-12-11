<?php
/**
 * ownCloud - Audio Player
 *
 * @author Marcel Scherello
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
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