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

class Reset extends Command {
	private $userManager;
	private $reset;
	public function __construct(\OCP\IUserManager $userManager, $reset) {
		$this->userManager = $userManager;
		$this->reset = $reset;
		parent::__construct();
	}
	
	protected function configure() {
		$this
			->setName('audioplayer:reset')
			->setDescription('reset audio player library')
			->addArgument(
					'user_id',
					InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
					'reset the whole library of the given user(s)'
			)
			->addOption(
					'all',
					null,
					InputOption::VALUE_NONE,
					'reset the whole library of all known users'
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
			$output->writeln("<error>Please specify a valid user id to reset, \"--all\" to scan for all users<error>");
			return;
		}
		
		foreach ($users as $userId) {
			if (is_object($userId)) $user = $userId;
			else $user = $this->userManager->get($userId);

			if ($user === null) {
				$output->writeln("<error>User $userId does not exist</error>");
			} else {
				$userId = $user->getUID();
				$output->writeln("<info>Reset library for $userId</info>");
				$this->reset->resetMediaLibrary($userId, $output);
			}
		}
	}
}