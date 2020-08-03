<?php
/**
 * @package    quantummanager
 *
 * @author     Dmitry Tsymbal <cymbal@delo-design.ru>
 * @copyright  Copyright © 2019 Delo Design & NorrNext. All rights reserved.
 * @license    GNU General Public License version 3 or later; see license.txt
 * @link       https://www.norrnext.com
 */

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

/**
 * Quantummanagerconfig plugin.
 *
 * @package   quantumyoothemepro
 * @since     1.0.0
 */
class plgSystemQuantummanagerconfig extends CMSPlugin
{

    /**
     * Application object
     *
     * @var    CMSApplication
     * @since  1.0.0
     */
    protected $app;


    /**
     * Database object
     *
     * @var    DatabaseDriver
     * @since  1.0.0
     */
    protected $db;


    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;


    public function onContentPrepareForm(Form $form, $data)
    {
        $admin = $this->app->isClient('administrator');

        if(!$admin)
        {
            return;
        }


        $option = $this->app->input->get('option');
        $view = $this->app->input->get('view');
        $component = $this->app->input->get('component');

        if($option !== 'com_config' || $view !== 'component' || $component !== 'com_quantummanager')
        {
            return;
        }

        HTMLHelper::_('script', 'plg_system_quantummanagerconfig/importexport.js', [
            'version' => filemtime(__FILE__),
            'relative' => true
        ]);


        $toolbar = Toolbar::getInstance('toolbar');

        $root = Uri::getInstance()->toString(array('scheme', 'host', 'port'));
        $url = $root . '/administrator/index.php?' . http_build_query([
                'option' => 'com_ajax',
                'plugin' => 'quantummanagerconfig',
                'group' => 'system',
                'format' => 'raw',
                'task' => 'export'
            ]);

        $button = '<a href="' . $url . '" class="btn btn-small">'
            . '<span class="icon-download" aria-hidden="true"></span>'
            . Text::_('PLG_QUANTUMMANAGERCONFIG_BUTTON_EXPORT') . '</a>';
        $toolbar->appendButton('Custom', $button, 'generate');


        $button = '<input type="file" name="importjson" style="display: none"><button class="btn btn-small btn-import">'
            . '<span class="icon-upload" aria-hidden="true"></span>'
            . Text::_('PLG_QUANTUMMANAGERCONFIG_BUTTON_IMPORT') . '</button>';
        $toolbar->appendButton('Custom', $button, 'generate');

    }


    public function onAjaxQuantummanagerconfig()
    {
        $task = $this->app->input->get('task');

        if($task === 'export')
        {
            $this->export();
        }

        if($task === 'import')
        {
            $this->import();
        }

    }


    /**
     * Export params com_quantummanager
     *
     * @throws Exception
     */
    private function export()
    {
        $app = Factory::getApplication();
        $input = $this->app->input;

        $params = ComponentHelper::getParams('com_quantummanager');
        $file = 'export.json';

        $this->app->setHeader('Content-Disposition', 'attachment; filename=' . basename($file));
        $this->app->setHeader('Content-Transfer-Encoding', 'binary');
        $this->app->setHeader('Expires', '0');
        $this->app->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $this->app->setHeader('Pragma', 'public');
        $this->app->setHeader('Content-Length', filesize($file));
        $this->app->sendHeaders();

        ob_clean();
        flush();
        echo json_encode($params);

        $this->app->close();
    }


    /**
     * Import params for com_quantummanager
     *
     * @throws Exception
     * @since version
     */
    private function import()
    {
        $input = $this->app->input;
        $files = $input->files->getArray();

        if(isset($files['params']))
        {

            if($files['params']['error'] === 0)
            {
                $params_new = file_get_contents($files['params']['tmp_name']);
                $params = ComponentHelper::getParams('com_quantummanager');
                $params->merge(new Registry($params_new));

                $componentid = ComponentHelper::getComponent('com_quantummanager')->id;
                $table = Table::getInstance('extension');
                $table->load($componentid);
                $table->bind(['params' => $params->toString()]);

                if (!$table->check())
                {
                    $this->app->enqueueMessage('PLG_QUANTUMMANAGERCONFIG_IMPORTEXPORT_ERROR_DATABASE', 'error');
                }

                if (!$table->store())
                {
                    $this->app->enqueueMessage('PLG_QUANTUMMANAGERCONFIG_IMPORTEXPORT_ERROR_DATABASE', 'error');
                }

                JLoader::register('QuantummanagerHelper', JPATH_SITE . '/administrator/components/com_quantummanager/helpers/quantummanager.php');
                QuantummanagerHelper::cleanCache('_system', 0);
                QuantummanagerHelper::cleanCache('_system', 1);

                $this->app->enqueueMessage('PLG_QUANTUMMANAGERCONFIG_IMPORTEXPORT_UPLOAD_SUCCESS', 'success');
            }
            else
            {
                $this->app->enqueueMessage('PLG_QUANTUMMANAGERCONFIG_IMPORTEXPORT_ERROR_FILE_UPLOAD', 'error');
            }

        }
        else
        {
            $this->app->enqueueMessage('PLG_QUANTUMMANAGERCONFIG_IMPORTEXPORT_ERROR_FILE_NOTFOUND', 'error');
        }

    }



}