<?php


namespace Convo\Core\Migrate;


use Convo\Core\Adapters\Alexa\AmazonSkillManifest;

class MigrateTo31 extends AbstractMigration
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getVersion()
    {
        return 31;
    }

    public function migrateConfig($config)
    {

        if (isset($config["amazon"])) {
            if (!isset($config["amazon"]["skill_preview_in_store"])) {
                $invocation = $config["amazon"]['invocation'] ?? 'default name';
                $name = $this->_invocationToName($invocation);

                if (!isset($config["amazon"]["skill_preview_in_store"]['public_name'])) {
                    $config["amazon"]["skill_preview_in_store"]['public_name'] = $name;
                }
                if (!isset($config["amazon"]["skill_preview_in_store"]['one_sentence_description'])) {
                    $config["amazon"]["skill_preview_in_store"]['one_sentence_description'] = $name;
                }
                if (!isset($config["amazon"]["skill_preview_in_store"]['detailed_description'])) {
                    $config["amazon"]["skill_preview_in_store"]['detailed_description'] = $name;
                }
                if (!isset($config["amazon"]["skill_preview_in_store"]['category'])) {
                    $config["amazon"]["skill_preview_in_store"]['category'] = 'ALARMS_AND_CLOCKS';
                }
                if (!isset($config["amazon"]["skill_preview_in_store"]['example_phrases'])) {
                    $config["amazon"]["skill_preview_in_store"]['example_phrases'] = "Alexa, open " . $invocation;
                }
                if (!isset($config["amazon"]["skill_preview_in_store"]['small_skill_icon'])) {
                    $config["amazon"]["skill_preview_in_store"]['small_skill_icon'] = '';
                }
                if (!isset($config["amazon"]["skill_preview_in_store"]['large_skill_icon'])) {
                    $config["amazon"]["skill_preview_in_store"]['large_skill_icon'] = '';
                }
                if (!isset($config["amazon"]["skill_preview_in_store"]['terms_of_use_url'])) {
                    $config["amazon"]["skill_preview_in_store"]['terms_of_use_url'] = '';
                }
                if (!isset($config["amazon"]["skill_preview_in_store"]['privacy_policy_url'])) {
                    $config["amazon"]["skill_preview_in_store"]['privacy_policy_url'] = '';
                }
                if (!isset($config["amazon"]["skill_preview_in_store"]['keywords'])) {
                    $config["amazon"]["skill_preview_in_store"]['keywords'] = '';
                }
                if (!isset($config["amazon"]["skill_preview_in_store"]['whats_new'])) {
                    $config["amazon"]["skill_preview_in_store"]['whats_new'] = '';
                }
            }

            if (!isset($config["amazon"]["privacy_and_compliance"])) {
                if (!isset($config["amazon"]["privacy_and_compliance"]['allows_purchases'])) {
                    $config["amazon"]["privacy_and_compliance"]['allows_purchases'] = false;
                }
                if (!isset($config["amazon"]["privacy_and_compliance"]['uses_personal_info'])) {
                    $config["amazon"]["privacy_and_compliance"]['uses_personal_info'] = false;
                }
                if (!isset($config["amazon"]["privacy_and_compliance"]['is_child_directed'])) {
                    $config["amazon"]["privacy_and_compliance"]['is_child_directed'] = false;
                }
                if (!isset($config["amazon"]["privacy_and_compliance"]['contains_ads'])) {
                    $config["amazon"]["privacy_and_compliance"]['contains_ads'] = false;
                }
                if (!isset($config["amazon"]["privacy_and_compliance"]['is_export_compliant'])) {
                    $config["amazon"]["privacy_and_compliance"]['is_export_compliant'] = true;
                }
                if (!isset($config["amazon"]["privacy_and_compliance"]['testing_instructions'])) {
                    $config["amazon"]["privacy_and_compliance"]['testing_instructions'] = 'N/A';
                }
            }
        }
        return parent::migrateConfig($config);
    }

    private function _invocationToName($invocation)
    {
        return ucwords($invocation);
    }
}
