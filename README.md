Radmiraal.SurfJobs
==================

A package containing job examples and tasks for TYPO3.Surf. It's meant
as an example (but usable) implementation of the rsync task created
by @daKmoR in https://github.com/daKmoR/TYPO3.Surf

Check http://forge.typo3.org/issues/37077 for the original issue on Forge.

Todo:
-----

Discuss if the 'transferMethod' should be supported. This could make it
more complex (for example if someone wants to transfer using rsync, but
wants to install using composer on the remote host).
https://github.com/daKmoR/TYPO3.Surf/commit/ec8ba795a4b0097db76db261ed9af9189ae74968

- [ ] TransferMethod
- [ ] Merge the LocalGitCheckoutTask with the CheckoutTask
- [ ] Discuss possible merging of the Flow application

Example job:
------------

```php
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\SimpleWorkflow;

$application = new \Radmiraal\SurfJobs\Application\TYPO3\Flow();
$application->setOption('version', '1.0.0');
$application->setOption('repositoryUrl', 'git@github.com:radmiraal/EmptyFlowDistribution.git');
$application->setOption('localCheckoutPath', FLOW_PATH_DATA . '/Checkout');
$application->setOption('composerCommandPath', '/opt/local/bin/composer');

$application->setDeploymentPath(FLOW_PATH_DATA . '/Deployments');

$deployment->addApplication($application);

$workflow = new SimpleWorkflow();
$workflow->setEnableRollback(FALSE);

$deployment->setWorkflow($workflow);

$deployment->onInitialize(function() use ($workflow, $application) {
	$workflow->removeTask('typo3.surf:flow:setfilepermissions');
});

$node = new Node('localhost');
$node->setHostname('localhost');
$node->setOption('username', 'rens');
$application->addNode($node);
```