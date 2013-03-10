<?php
namespace Radmiraal\SurfJobs\Application\TYPO3;

use TYPO3\Surf\Domain\Model\Workflow;
use TYPO3\Surf\Domain\Model\Deployment;

/**
 * Class Flow
 * @TYPO3\Flow\Annotations\Proxy(false)
 */
class Flow extends \TYPO3\Surf\Application\TYPO3\Flow {

	public function registerTasks(Workflow $workflow, Deployment $deployment) {
		$workflow->setTaskOptions(
			'radmiraal.surfjobs:localgitcheckout',
			array(
				'sha1' => $this->hasOption('git-checkout-sha1') ? $this->getOption('git-checkout-sha1') : NULL,
				'tag' => $this->hasOption('git-checkout-tag') ? $this->getOption('git-checkout-tag') : NULL,
				'branch' => $this->hasOption('git-checkout-branch') ? $this->getOption('git-checkout-branch') : NULL
			));

		$workflow->setTaskOptions(
			'typo3.surf:generic:createDirectories',
			array(
				'directories' => $this->getDirectories()
			));
		$workflow->setTaskOptions(
			'typo3.surf:generic:createSymlinks',
			array(
				'symlinks' => $this->getSymlinks()
			));

		$workflow
			->addTask('typo3.surf:typo3:flow:createdirectories', 'initialize', $this)
			->addTask('radmiraal.surfjobs:localgitcheckout', 'update', $this)
			->addTask('typo3.surf:symlinkrelease', 'switch', $this)
			->addTask('typo3.surf:cleanupreleases', 'cleanup', $this);

		$workflow->afterTask(
				'radmiraal.surfjobs:localgitcheckout',
				array(
					'typo3.surf:composer:install',
					'radmiraal.surfjobs:rsync'
				)
			)
			->afterTask('typo3.surf:createdirectories', 'typo3.surf:generic:createDirectories', $this)
			->afterTask('radmiraal.surfjobs:rsync', array(
				'typo3.surf:typo3:flow:symlinkdata',
				'typo3.surf:typo3:flow:symlinkconfiguration',
				'typo3.surf:generic:createSymlinks'
			))
			->addTask('typo3.surf:typo3:flow:migrate', 'migrate', $this);
	}

}

?>