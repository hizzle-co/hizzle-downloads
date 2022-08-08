<?php

    namespace Hizzle\Downloads\Admin;

    /**
     * Admin View: Downloads edit page.
     *
     * @var \Hizzle\Downloads\Download $download
     */

    defined( 'ABSPATH' ) || exit;

    $conditional_logic = $download->get_conditional_logic();

	$conditional_logic['allRules'] = hizzle_downloads_get_conditional_logic_rules();
?>

<div class="wrap hizzle-downloads-page" id="hizzle-downlads-wrapper">

    <h1 class="wp-heading-inline">
        <?php if ( $download->exists() ) : ?>
            <span><?php esc_html_e( 'Edit Downloadable File', 'hizzle-downloads' ); ?></span>
            <a href="<?php echo esc_url( add_query_arg( 'hizzle_download', '0', admin_url( 'admin.php?page=hizzle-downloads' ) ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'hizzle-downloads' ); ?></a>
        <?php else : ?>
		    <span><?php esc_html_e( 'Add Downloadable File', 'hizzle-downloads' ); ?></span>
        <?php endif; ?>
    </h1>

    <form id="hizzle-edit-download" method="POST">
        <?php Admin::action_field( 'save_download' ); ?>
        <input type="hidden" name="hizzle_download_id" value="<?php echo esc_attr( $download->get_id() ); ?>" />

        <!-- File Name -->
        <div class="hizzle-downloads-form-field">
            <label for="hizzle-file-name" class="hizzle-downloads-form-field-label"><?php esc_html_e( 'File Name', 'hizzle-downloads' ); ?></label>
            <div class="hizzle-downloads-form-field-input">
                <input type="text" class="regular-text" name="hizzle_downloads[file_name]" id="hizzle-file-name" value="<?php echo esc_attr( $download->get_file_name() ); ?>" placeholder="<?php esc_attr_e( 'For example, my-file.zip', 'hizzle-downloads' ); ?>" />
                <p class="description"><?php esc_html_e( 'The file name that is shown to users.', 'hizzle-downloads' ); ?></p>
            </div>
        </div>

        <!-- File URL -->
        <div class="hizzle-downloads-form-field">
            <label for="hizzle-file-url" class="hizzle-downloads-form-field-label"><?php esc_html_e( 'File URL', 'hizzle-downloads' ); ?></label>
            <div class="hizzle-downloads-form-field-input">
                <input type="text" class="regular-text" name="hizzle_downloads[file_url]" id="hizzle-file-url" value="<?php echo esc_attr( $download->get_file_url() ); ?>" placeholder="<?php esc_attr_e( 'For example, https://example.com/my-file.zip', 'hizzle-downloads' ); ?>" />
                <button class="button button-secondary hizzle-upload-downloadable-file"><?php esc_html_e( 'Upload File', 'hizzle-downloads' ); ?></button>
                <p class="description"><?php esc_html_e( 'The URL to the downloadable file.', 'hizzle-downloads' ); ?></p>
            </div>
        </div>

        <!-- File category -->
        <div class="hizzle-downloads-form-field">
            <label for="hizzle-file-category" class="hizzle-downloads-form-field-label"><?php esc_html_e( 'File Category', 'hizzle-downloads' ); ?></label>
            <div class="hizzle-downloads-form-field-input">
                <input type="text" class="regular-text" name="hizzle_downloads[category]" id="hizzle-file-url" value="<?php echo esc_attr( $download->get_category() ); ?>" placeholder="<?php esc_attr_e( 'For example, General', 'hizzle-downloads' ); ?>" />
                <p class="description"><?php esc_html_e( 'Optional. If specified, this download will be grouped together with other downloads in the same category.', 'hizzle-downloads' ); ?></p>
            </div>
        </div>

        <!-- Menu order -->
        <div class="hizzle-downloads-form-field">
            <label for="hizzle-menu-order" class="hizzle-downloads-form-field-label"><?php esc_html_e( 'Priority', 'hizzle-downloads' ); ?></label>
            <div class="hizzle-downloads-form-field-input">
                <input type="number" class="regular-text" name="hizzle_downloads[menu_order]" id="hizzle-menu-order" value="<?php echo esc_attr( $download->get_menu_order() ); ?>" placeholder="0"/>
                <p class="description"><?php esc_html_e( 'The priority this file should appear when viewing download files.', 'hizzle-downloads' ); ?></p>
            </div>
        </div>

        <!-- Conditional logic -->
        <div class="hizzle-downloads-form-field" id="hizzle-downloads-edit-conditional-logic-app" data-conditional-logic="<?php echo esc_attr( wp_json_encode( $conditional_logic ) ); ?>">
            <label for="hizzle-enable-conditional-logic" class="hizzle-downloads-form-field-label"><?php esc_html_e( 'Conditional Logic', 'hizzle-downloads' ); ?></label>
            <div class="hizzle-downloads-form-field-input">
                <p class="description">
                    <label>
                        <input type="checkbox" name="hizzle_downloads[conditional_logic][enabled]" id="hizzle-enable-conditional-logic" value="1" v-model="enabled" />
                        <span><?php esc_html_e( 'Optionally allow/prevent downloads depending on specific conditions.', 'hizzle-downloads' ); ?></span>
                    </label>
                </p>

                <div class="hizzle-downloads-conditional-logic-wrapper card" v-show="enabled">

                    <p>
                        <select name="hizzle_downloads[conditional_logic][action]" v-model="action">
                            <option value="allow"><?php esc_html_e( 'Only allow', 'hizzle-downloads' ); ?></option>
                            <option value="prevent"><?php esc_html_e( 'Prevent', 'hizzle-downloads' ); ?></option>
                        </select>

                        <span>&nbsp;<?php esc_html_e( 'downloads if', 'hizzle-downloads' ); ?>&nbsp;</span>

                        <select name="hizzle_downloads[conditional_logic][type]" v-model="type">
                            <option value="all"><?php esc_html_e( 'all', 'hizzle-downloads' ); ?></option>
                            <option value="any"><?php esc_html_e( 'any', 'hizzle-downloads' ); ?></option>
                        </select>

                        <span>&nbsp;<?php esc_html_e( 'of the following rules are true:', 'hizzle-downloads' ); ?>&nbsp;</span>
                    </p>

                    <p v-for="(rule, index) in rules" class="hizzle-downloads-conditional-logic-rule">

                        <select :name="'hizzle_downloads[conditional_logic][rules][' + index + '][type]'" class="hizzle-logic-rule-input-lg" v-model="rule.type" @change="rule.value=''">
                            <option v-for="(rule_type, rule_key) in allRules" :value="rule_key">{{ rule_type.label }}</option>
                        </select>

                        <select :name="'hizzle_downloads[conditional_logic][rules][' + index + '][condition]'" v-model="rule.condition">
                            <option value="is"><?php esc_html_e( 'is', 'hizzle-downloads' ); ?></option>
                            <option value="is_not"><?php esc_html_e( 'is not', 'hizzle-downloads' ); ?></option>
                        </select>

                        <select :name="'hizzle_downloads[conditional_logic][rules][' + index + '][value]'" class="hizzle-logic-rule-input-lg" v-model="rule.value" v-if="hasRuleOptions(rule.type)">
                            <option v-for="(option_label, option_value) in getRuleOptions(rule.type)" :value="option_value">{{ option_label }}</option>
                        </select>

                        <input type="text" :name="'hizzle_downloads[conditional_logic][rules][' + index + '][value]'" class="hizzle-logic-rule-input-lg" v-model="rule.value" v-else />

                        <a href="#" @click.prevent="removeRule(rule)" class="hizzle-downloads-remove-rule">
                            <span class="dashicons dashicons-remove"></span>&nbsp;
                        </a>

                        <span v-if="! isLastRule(index) && 'all' == type">&nbsp;<?php esc_html_e( 'and', 'hizzle-downloads' ); ?></span>
                        <span v-if="! isLastRule(index) && 'any' == type">&nbsp;<?php esc_html_e( 'or', 'hizzle-downloads' ); ?></span>
                    </p>

                    <p>
                        <button class="button" @click.prevent="addRule">
                            <span class="dashicons dashicons-plus" style="vertical-align: middle;"></span>
                            <span v-if="rules"><?php esc_html_e( 'Add another rule', 'hizzle-downloads' ); ?></span>
                            <span v-else><?php esc_html_e( 'Add rule', 'hizzle-downloads' ); ?></span>
                        </button>
                    </p>
                </div>
            </div>
        </div>

        <?php submit_button( __( 'Save Download', 'hizzle-downloads' ), 'primary', 'hizzle-save-download' ); ?>

    </form>
</div>
