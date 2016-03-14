<?php
namespace Neoslive\Allowedsites\Aspects;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\Flow\Core\Bootstrap;

/**
 * @Flow\Aspect
 */
class AllowedSitesAspect {

    /**
     *    ===================================
     *    Node Type allowed sites constraints
     *    ===================================
     *
     *    In an advanded Neos project, you will create lots of sites and node types. However, many node types should only be
     *    used in a specific site and not in every site.
     *
     *    For instance, the node type "Chapter" should only exists within the Neos site "TYPO3.NeosDemoTypo30org",
     *    this can be enforced::
     *
     *    'TYPO3.NeosDemoTypo3Org:Chapter':
     *      allowedSites:
     *        'TYPO3.NeosDemoTypo3Org': TRUE
     *        '*': FALSE
     *
     *    In the above example, we disable the node type for all sites using ``*: FALSE``, and then enable the ``Chapter`` node type as well
     *    as any node type that super types it for the site 'TYPO3.NeosDemoTypo30org'. Not allowed node types will not appear anymore in the sites
     *    content, structur and any wizards. As well you can change the allowedSites contraint whenever you want.
     *
     */

    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;


    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;


    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;


    /**
     * Check if nodetype is in allowed sites (apply node context by filtering out not allowed node types)
     *
     * @param \TYPO3\Flow\AOP\JoinPointInterface $joinPoint
     * @Flow\Around("method(TYPO3\TYPO3CR\Domain\Factory\NodeFactory->filterNodeByContext(.*))")
     * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface|NULL
     */
    public function filterNodeByAllowedSites($joinPoint) {


        if ($this->isNodeTypeInAllowedSite($joinPoint->getMethodArgument('node')->getNodeType()) == FALSE) return null;

        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
        return $result;

    }

    /**
     * Check if nodetype is in allowed sites (apply nodetype scheme by unsetting not allowed node types)
     *
     * @param \TYPO3\Flow\AOP\JoinPointInterface $joinPoint
     * @Flow\Around("method(TYPO3\TYPO3CR\Domain\Service\NodeTypeManager->getNodeTypes(.*))")
     * @return boolean
     */
    public function grantNodeByAllowedSites($joinPoint) {


        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);

        foreach ($result as $nodeName => $nodeType) {
            if ($this->isNodeTypeInAllowedSite($nodeType) == FALSE) unset($result[$nodeName]);
        }

        return $result;


    }



    /**
     * @param NodeType $nodetype
     * @return boolean
     */
    private function isNodeTypeInAllowedSite(NodeType $nodetype) {

        // dont restrict nodetypes outside httpd requests
        if ($this->bootstrap->getActiveRequestHandler() instanceof \TYPO3\Flow\Http\HttpRequestHandlerInterface === false) return true;

        // dont restrict abstract nodes
        if ($nodetype->isAbstract()) return true;

        $deniedWilcard = false;
        $allowedSites = $nodetype->getConfiguration('allowedSites');

        if ($allowedSites) {

            $currentDomain = $this->domainRepository->findOneByActiveRequest();


            if ($currentDomain !== null) {
                $currentSite = $currentDomain->getSite();
            } else {
                $currentSite = $this->siteRepository->findFirstOnline();
            }

            if (!$currentSite) return true;

            foreach ($allowedSites as $siteName => $allowed) {
                if ($allowed == TRUE && ($siteName == '*' | $currentSite->getSiteResourcesPackageKey() == $siteName)) return true;
                if ($allowed == FALSE && $currentSite->getSiteResourcesPackageKey() == $siteName) return false;
                if ($allowed == TRUE && $siteName == '*') $deniedWilcard = false;
                if ($allowed == FALSE && $siteName == '*') $deniedWilcard = true;
            }

            if ($deniedWilcard == true) return false;

        }

        return true;

    }


}