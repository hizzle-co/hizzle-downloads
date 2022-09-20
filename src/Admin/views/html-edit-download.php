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

    <?php if ( $download->exists() ) : ?>
        <p class="description">
            <?php
                printf(
                    // translators: %s is a shortcode.
                    esc_html__( 'Use the %s shortcode to display a download link on the frontend.', 'hizzle-downloads' ),
                    '<code>[hizzle-download id="' . (int) $download->get_id() . '"]Download[/hizzle-download]</code>'
                );
            ?>
        </p>
    <?php endif; ?>

    <form id="hizzle-edit-download" method="POST">
        <?php Admin::action_field( 'save_download' ); ?>
        <input type="hidden" name="hizzle_download_id" value="<?php echo esc_attr( $download->get_id() ); ?>" />

        <!-- File Name -->
        <div class="hizzle-downloads-form-field">
            <label for="hizzle-file-name" class="hizzle-downloads-form-field-label"><?php esc_html_e( 'File Name', 'hizzle-downloads' ); ?></label>
            <div class="hizzle-downloads-form-field-input">
                <input type="text" class="regular-text" name="hizzle_downloads[file_name]" id="hizzle-file-name" value="<?php echo esc_attr( $download->get_file_name() ); ?>" placeholder="<?php esc_attr_e( 'For example, My Ebook', 'hizzle-downloads' ); ?>"/>
                <p class="description"><?php esc_html_e( 'The file name that is shown to users.', 'hizzle-downloads' ); ?></p>
            </div>
        </div>

        <!-- File URL -->
        <div class="hizzle-downloads-form-field">
            <label for="hizzle-file-url" class="hizzle-downloads-form-field-label"><?php esc_html_e( 'File URL', 'hizzle-downloads' ); ?></label>
            <div class="hizzle-downloads-form-field-input">
                <input type="text" class="regular-text" name="hizzle_downloads[file_url]" id="hizzle-file-url" value="<?php echo esc_attr( $download->get_file_url( 'edit' ) ); ?>" placeholder="<?php printf( /* translators: %s Example URL */ esc_attr__( 'For example, %s', 'hizzle-downloads' ), 'https://example.com/my-file.zip' ); ?>"/>
                <button class="button button-secondary hizzle-upload-downloadable-file"><?php esc_html_e( 'Upload File', 'hizzle-downloads' ); ?></button>
                <p class="description"><?php esc_html_e( 'The URL to the downloadable file.', 'hizzle-downloads' ); ?></p>
            </div>
        </div>

        <?php if ( hizzle_downloads_using_github_updater() ) : ?>
            <!-- Repo URL -->
            <div class="hizzle-downloads-form-field">
                <label for="hizzle-git-url" class="hizzle-downloads-form-field-label"><?php esc_html_e( 'GitHub URL', 'hizzle-downloads' ); ?></label>
                <div class="hizzle-downloads-form-field-input">
                    <input type="text" class="regular-text" name="hizzle_downloads[git_url]" id="hizzle-git-url" value="<?php echo esc_attr( $download->get_git_url( 'edit' ) ); ?>" placeholder="<?php printf( /* translators: %s Example URL */ esc_attr__( 'For example, %s', 'hizzle-downloads' ), 'https://github.com/octocat/Hello-World/' ); ?>"/>
                    <input type="hidden" id="hizzle-git-tag" value="latest">
                    <input type="hidden" id="hizzle-git-update-key" name="hizzle_downloads[git_update_key]">
                    <button class="button button-secondary hizzle-downloads-fetch-github-repo"><?php esc_html_e( 'Fetch', 'hizzle-downloads' ); ?></button>
                    <span class="spinner hizzle-git-url-spinner" style="float: none;"></span>
                    <p class="description"><?php esc_html_e( 'The URL to the GitHub repo if this file is hosted on GitHub.', 'hizzle-downloads' ); ?></p>
                    <p class="hizzle-git-url-error" style="display: none;"></p>
                    <p class="hizzle-git-url-success" style="display: none;"><?php esc_html_e( 'Repo fetched successfuly.', 'hizzle-downloads' ); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- File category -->
        <div class="hizzle-downloads-form-field">
            <label for="hizzle-file-category" class="hizzle-downloads-form-field-label"><?php esc_html_e( 'File Category', 'hizzle-downloads' ); ?></label>
            <div class="hizzle-downloads-form-field-input">
                <input type="text" class="regular-text" name="hizzle_downloads[category]" id="hizzle-file-category" value="<?php echo esc_attr( $download->get_category( 'edit' ) ); ?>" placeholder="<?php esc_attr_e( 'For example, General', 'hizzle-downloads' ); ?>" />
                <p class="description"><?php esc_html_e( 'Optional. If specified, this download will be grouped together with other downloads in the same category.', 'hizzle-downloads' ); ?></p>
            </div>
        </div>

        <!-- File password -->
        <div class="hizzle-downloads-form-field">
            <label for="hizzle-file-password" class="hizzle-downloads-form-field-label"><?php esc_html_e( 'File Password', 'hizzle-downloads' ); ?></label>
            <div class="hizzle-downloads-form-field-input">
                <input type="password" class="regular-text" name="hizzle_downloads[password]" id="hizzle-file-password" value="<?php echo esc_attr( $download->get_password( 'edit' ) ); ?>" placeholder="<?php esc_html_e( 'No Password', 'hizzle-downloads' ); ?>" autocomplete="new-password" />
                <p class="description"><?php esc_html_e( 'If provided, users will be required to enter the password before being allowed to download this file.', 'hizzle-downloads' ); ?></p>
            </div>
        </div>

        <!-- Menu order -->
        <div class="hizzle-downloads-form-field">
            <label for="hizzle-menu-order" class="hizzle-downloads-form-field-label"><?php esc_html_e( 'Priority', 'hizzle-downloads' ); ?></label>
            <div class="hizzle-downloads-form-field-input">
                <input type="number" class="regular-text" name="hizzle_downloads[menu_order]" id="hizzle-menu-order" value="<?php echo esc_attr( $download->get_menu_order( 'edit' ) ); ?>" placeholder="0"/>
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
