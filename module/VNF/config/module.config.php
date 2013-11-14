<?php
namespace VNF\Module\Configuration;

$config = array(
   'vufind' => array(
      'plugin_managers' => array (
         'recorddriver' => array (
            'factories' => array(
               'solrmkp' => function ($sm) {
                  $driver = new \VNF\RecordDriver\SolrMkp(
                      $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
                      null,
                      $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
				   );
                   $driver->attachILS(
                      $sm->getServiceLocator()->get('VuFind\ILSConnection'),
                      $sm->getServiceLocator()->get('VuFind\ILSHoldLogic'),
                      $sm->getServiceLocator()->get('VuFind\ILSTitleHoldLogic')
                   );
                   return $driver;
               }, /* solrmkp */
               'solrcbvk' => function ($sm) {
                  $driver = new \VuFind\RecordDriver\SolrMarc(
                     $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
                     null,
                     $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
                  );
                  $driver->attachILS(
                     $sm->getServiceLocator()->get('VuFind\ILSConnection'),
                     $sm->getServiceLocator()->get('VuFind\ILSHoldLogic'),
                     $sm->getServiceLocator()->get('VuFind\ILSTitleHoldLogic')
                  );
                  return $driver;
               }, /* solrcbvk */
               'solrmzk' => function ($sm) {
                  $driver = new \VuFind\RecordDriver\SolrMarc(
                     $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
                     null,
                     $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
                  );
                  $driver->attachILS(
                     $sm->getServiceLocator()->get('VuFind\ILSConnection'),
                     $sm->getServiceLocator()->get('VuFind\ILSHoldLogic'),
                     $sm->getServiceLocator()->get('VuFind\ILSTitleHoldLogic')
                  );
                  return $driver;
               }, /* solrmzk */
               'solrvnf' => function ($sm) {
                  $driver = new \VuFind\RecordDriver\SolrMarc(
                     $sm->getServiceLocator()->get('VuFind\Config')->get('config'),
                     null,
                     $sm->getServiceLocator()->get('VuFind\Config')->get('searches')
                  );
                  $driver->attachILS(
                     $sm->getServiceLocator()->get('VuFind\ILSConnection'),
                     $sm->getServiceLocator()->get('VuFind\ILSHoldLogic'),
                     $sm->getServiceLocator()->get('VuFind\ILSTitleHoldLogic')
                  );
                  return $driver;
               }, /* solrvnf */
            ) /* factories */
         ) /* recorddriver */
      ) /* plugin_managers */
   ) /* vufind */
);

return $config;
