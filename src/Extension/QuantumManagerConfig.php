<?php

namespace Joomla\Plugin\System\QuantumManagerConfig\Extension;

/**
 * @package    quantummanager
 *
 * @author     Dmitry Tsymbal <cymbal@delo-design.ru>
 * @copyright  Copyright © 2019 Delo Design & NorrNext. All rights reserved.
 * @license    GNU General Public License version 3 or later; see license.txt
 * @link       https://www.norrnext.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\Extension;
use Joomla\CMS\Toolbar\ToolbarFactoryInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Component\QuantumManager\Administrator\Helper\QuantummanagerHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

class QuantumManagerConfig extends CMSPlugin implements SubscriberInterface
{

	protected $app;

	protected $db;

	protected $autoloadLanguage = true;

	protected $elements = [
		[
			'type'    => 'component',
			'element' => 'com_quantummanager'
		],
		[
			'type'    => 'plugin',
			'element' => 'quantummanagerbutton'
		],
		[
			'type'    => 'plugin',
			'element' => 'quantummanagermedia'
		]
	];

	public static function getSubscribedEvents(): array
	{
		return [
			'onContentPrepareForm'       => 'onContentPrepareForm',
			'onAjaxQuantummanagerconfig' => 'onAjax',
		];
	}

	public function onContentPrepareForm(PrepareFormEvent $event): void
	{
		$admin = $this->app->isClient('administrator');

		if (!$admin)
		{
			return;
		}

		$element = '';
		$find    = false;
		$option  = $this->app->input->get('option');

		if ($option === 'com_config')
		{
			$view       = $this->app->input->get('view');
			$component  = $this->app->input->get('component');
			$components = [];

			foreach ($this->elements as $element)
			{
				if ($element['type'] === 'component')
				{
					$components[] = $element['element'];
				}
			}

			if ($view === 'component' && in_array($component, $components))
			{
				$element = $component;
				$find    = true;
			}
		}

		if ($option === 'com_plugins')
		{
			$view = $this->app->input->get('view');

			if ($view !== 'plugin')
			{
				return;
			}

			$extension_id = $this->app->input->getInt('extension_id');
			$plugins      = [];
			$plugin       = '';

			$table = new Extension(Factory::getContainer()->get(DatabaseDriver::class));
			$table->load($extension_id);

			if (!empty($table->element))
			{
				$plugin = $table->element;
			}
			else
			{
				return;
			}

			foreach ($this->elements as $element)
			{
				if ($element['type'] === 'plugin')
				{
					$plugins[] = $element['element'];
				}
			}

			if (in_array($plugin, $plugins))
			{
				$element = $plugin;
				$find    = true;
			}
		}

		if (!$find)
		{
			return;
		}

		HTMLHelper::_('script', 'plg_system_quantummanagerconfig/importexport.js', [
			'version'  => filemtime(__FILE__),
			'relative' => true
		]);

		$toolbar = Factory::getContainer()->get(ToolbarFactoryInterface::class)->createToolbar('toolbar');

		$root = Uri::getInstance()->toString(array('scheme', 'host', 'port'));
		$url  = $root . '/administrator/index.php?' . http_build_query([
				'option'  => 'com_ajax',
				'plugin'  => 'quantummanagerconfig',
				'group'   => 'system',
				'format'  => 'raw',
				'task'    => 'export',
				'element' => $element
			]);

		/** @var WebAssetManager $wa */
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->addInlineStyle('#toolbar-quantum-config-button-position{float:right}');
		$wa->addInlineScript("window.QuantummanagerConfig = {alert: '" . htmlspecialchars(Text::_('PLG_QUANTUMMANAGERCONFIG_ALERT'), ENT_QUOTES) . "'}");

		$button = '<a href="' . $url . '" class="btn btn-small">'
			. '<span class="icon-download" aria-hidden="true"></span>'
			. Text::_('PLG_QUANTUMMANAGERCONFIG_BUTTON_EXPORT') . '</a>';
		$toolbar->appendButton('Custom', $button, 'quantum-config-button-position');

		$button = '<input type="file" name="importjson" style="display: none"><button data-element="' . $element . '" class="btn btn-small btn-import">'
			. '<span class="icon-upload" aria-hidden="true"></span>'
			. Text::_('PLG_QUANTUMMANAGERCONFIG_BUTTON_IMPORT') . '</button>';
		$toolbar->appendButton('Custom', $button, 'quantum-config-button-position');
	}

	public function onAjax(): void
	{
		$admin = $this->app->isClient('administrator');

		if (!$admin)
		{
			return;
		}

		$task = $this->app->input->get('task');

		if ($task === 'export')
		{
			$this->export();
		}

		if ($task === 'import')
		{
			$this->import();
		}
	}

	private function checkElement(string $element_check): void
	{
		$find = false;

		foreach ($this->elements as $element)
		{
			if ($element['element'] === $element_check)
			{
				$find = true;
			}
		}

		if (!$find)
		{
			$this->app->close(401);
		}
	}

	private function export(): void
	{
		$element = $this->app->input->get('element');

		$this->checkElement($element);

		$table = new Extension(Factory::getContainer()->get(DatabaseDriver::class));
		$table->load(['element' => $element]);

		$params = new Registry($table->params);
		$file   = 'export_' . $element . '.json';

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

	private function import(): void
	{
		$input   = $this->app->input;
		$files   = $input->files->getArray();
		$element = $this->app->input->get('element');

		$this->checkElement($element);

		if (isset($files['params']))
		{
			if ($files['params']['error'] === 0)
			{
				$table = new Extension(Factory::getContainer()->get(DatabaseDriver::class));
				$table->load(['element' => $element]);
				$params_new = file_get_contents($files['params']['tmp_name']);

				if (!is_array(json_decode($params_new, JSON_OBJECT_AS_ARRAY)))
				{
					$this->app->enqueueMessage('PLG_QUANTUMMANAGERCONFIG_IMPORTEXPORT_ERROR_FILE_UPLOAD', 'error');

					return;
				}

				$params = new Registry($table->params);
				$params->merge(new Registry($params_new));
				$table->bind(['params' => $params->toString()]);

				if (!$table->check())
				{
					$this->app->enqueueMessage('PLG_QUANTUMMANAGERCONFIG_IMPORTEXPORT_ERROR_DATABASE', 'error');

					return;
				}

				if (!$table->store())
				{
					$this->app->enqueueMessage('PLG_QUANTUMMANAGERCONFIG_IMPORTEXPORT_ERROR_DATABASE', 'error');

					return;
				}

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