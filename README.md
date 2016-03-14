# Neoslive.AllowedSites
Make nodetypes visible only specific sites.

 In an advanded Neos project, you will create lots of sites and node types. However, many node types should only be used in a specific site and not in every site.
 
 For instance, the node type "Chapter" should only exists within the Neos site "TYPO3.NeosDemoTypo30org", this can be enforced:
 
    'TYPO3.NeosDemoTypo3Org:Chapter':
      allowedSites:
        'TYPO3.NeosDemoTypo3Org': TRUE
        '*': FALSE
        
In the above example, we disable the node type for all sites using ``*: FALSE``, and then enable the ``Chapter`` node type as well as any node type that super types it for the site 'TYPO3.NeosDemoTypo30org'. Not allowed node types will not appear anymore in the sites content, structur and any wizards. As well you can change the allowedSites contraint whenever you want.
