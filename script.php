<?php
/**
 * @package    quantumyoothemepro
 *
 * @author     tsymb <your@email.com>
 * @copyright  A copyright
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       http://your.url.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;

/**
 * Quantumyoothemepro script file.
 *
 * @package   quantumyoothemepro
 * @since     1.0.0
 */
class plgSystemQuantummanagerconfigInstallerScript
{

    /**
     * Called after any type of action
     *
     * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
     * @param   JAdapterInstance  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function postflight($route, JAdapterInstance $adapter) {
        $db = Factory::getDbo();
        $query = $db->getQuery( true );
        $query->update( '#__extensions' )
            ->set( 'enabled=1' )
            ->where( 'type=' . $db->q( 'plugin' ) )
            ->where( 'element=' . $db->q( 'quantummanagerconfig' ) );
        $db->setQuery( $query )->execute();
    }


}