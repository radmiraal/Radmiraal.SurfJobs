<?php
namespace Radmiraal\SurfJobs\Task;

use TYPO3\Flow\Annotations as Flow;

use TYPO3\Flow\Utility\Files;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;

/**
 * A generic checkout task
 *
 */
class RsyncTask extends \TYPO3\Surf\Domain\Model\Task {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * Execute this task
	 *
	 * @param \TYPO3\Surf\Domain\Model\Node $node
	 * @param \TYPO3\Surf\Domain\Model\Application $application
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param array $options
	 * @return void
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$localPath = $options['localCheckoutPath'];
		$releasePath = $deployment->getApplicationReleasePath($application);
		$remotePath = Files::concatenatePaths(array($application->getDeploymentPath(), 'cache/'));

		// make sure there is a remote .cache folder
		$command = 'mkdir -p ' . $remotePath;
		$this->shell->executeOrSimulate($command, $node, $deployment);

		$username = $node->getOption('username');
		$hostname = $node->getHostname();
		$port = $node->hasOption('port') ? '-p ' . $node->getOption('port') : '';
		$quietFlag = (isset($options['verbose']) && $options['verbose']) ? '' : '-q';

		$command = "rsync {$quietFlag} --compress --rsh='ssh {$port}' --recursive --times --perms --links --delete --exclude '.git' {$localPath}/* {$username}@{$hostname}:{$remotePath}";

		if ($node->hasOption('password')) {
			$surfPackage = $this->packageManager->getPackage('TYPO3.Surf');
			$passwordSshLoginScriptPathAndFilename = Files::concatenatePaths(array($surfPackage->getResourcesPath(), 'Private/Scripts/PasswordSshLogin.expect'));
			$command = sprintf('expect %s %s %s', escapeshellarg($passwordSshLoginScriptPathAndFilename), escapeshellarg($node->getOption('password')), $command);
		}

		$localhost = new Node('localhost');
		$localhost->setHostname('localhost');
		$this->shell->executeOrSimulate($command, $localhost, $deployment);

		$command = strtr("
			cp -RPp $remotePath/. $releasePath
				&& (echo > $releasePath" . "REVISION)
			", "\t\n", "  ");

		$this->shell->executeOrSimulate($command, $node, $deployment);
	}

	/**
	 * Simulate this task
	 *
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 */
	public function simulate(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$this->execute($node, $application, $deployment, $options);
	}

	/**
	 * Rollback this task
	 *
	 * @param \TYPO3\Surf\Domain\Model\Node $node
	 * @param \TYPO3\Surf\Domain\Model\Application $application
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param array $options
	 * @return void
	 */
	public function rollback(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$releasePath = $deployment->getApplicationReleasePath($application);
		$this->shell->execute('rm -f ' . $releasePath, $node, $deployment, TRUE);
	}

}
?>