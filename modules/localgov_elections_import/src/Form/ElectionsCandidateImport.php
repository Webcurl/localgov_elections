<?php

namespace Drupal\localgov_elections_import\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\localgov_elections_import\MigrateBatchExecutable;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\localgov_elections_import\StubMigrationMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ElectionsCandidateImport extends FormBase {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $pluginManagerMigration;

  /**
   * The migration definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * MigrateSourceUiForm constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager_migration
   *   The migration plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(MigrationPluginManager $plugin_manager_migration) {
    $this->pluginManagerMigration = $plugin_manager_migration;
    $this->definitions = $this->pluginManagerMigration->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.migration')
    );
  }

  public function getFormId() {
      return 'localgov_elections_candidate_import';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['source_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload the source file'),
    ];
    $form['update_existing_records'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update existing records'),
      '#default_value' => 1,
    ];
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate'),
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

    $validators = ['file_validate_extensions' => ['xls', 'xlsx']];
    $file = file_save_upload('source_file', $validators, FALSE, 0, FileSystemInterface::EXISTS_REPLACE);

    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        $form_state->setValue('file_path', $file->getFileUri());
      }
      // File upload failed.
      else {
        $form_state->setErrorByName('source_file', $this->t('The file could not be uploaded.'));
      }
    }
    else {
      $form_state->setErrorByName('source_file', $this->t('You have to upload a source file.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $migration_id = 'elections_candidate_xpress';
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->pluginManagerMigration->createInstance($migration_id);

    // Reset status.
    $status = $migration->getStatus();
    if ($status !== MigrationInterface::STATUS_IDLE) {
      $migration->setStatus(MigrationInterface::STATUS_IDLE);
      $this->messenger()->addWarning($this->t('Migration @id reset to Idle', ['@id' => $migration_id]));
    }

    $options = [
      'file_path' => $form_state->getValue('file_path'),
      'election_node' => \Drupal::routeMatch()->getParameter('node')->id(),
    ];
    // Force updates or not.
    if ($form_state->getValue('update_existing_records')) {
      $options['update'] = TRUE;
    }

    $executable = new MigrateBatchExecutable($migration, new StubMigrationMessage(), $options);
    $executable->batchImport();
  }

}
