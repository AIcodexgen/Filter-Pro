<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class EF_Admin {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_ajax_ef_save_attorney',  [ $this, 'ajax_save_attorney' ] );
        add_action( 'wp_ajax_ef_delete_attorney',[ $this, 'ajax_delete_attorney' ] );
        add_action( 'wp_ajax_ef_save_combos',    [ $this, 'ajax_save_combos' ] );
        add_action( 'wp_ajax_ef_save_office',    [ $this, 'ajax_save_office' ] );
        add_action( 'wp_ajax_ef_delete_office',  [ $this, 'ajax_delete_office' ] );
        add_action( 'wp_ajax_ef_save_settings',  [ $this, 'ajax_save_settings' ] );
        add_action( 'wp_ajax_ef_reorder_attorneys', [ $this, 'ajax_reorder_attorneys' ] );
        add_filter( 'plugin_action_links_' . EF_BASENAME, [ $this, 'plugin_links' ] );
    }

    /* ── Menu ── */
    public function register_menu() {
        add_menu_page(
            'Attorney Directory',
            'Attorneys',
            'manage_options',
            'ef-attorneys',
            [ $this, 'page_attorneys' ],
            'dashicons-groups',
            30
        );
        add_submenu_page( 'ef-attorneys', 'Attorneys',      'All Attorneys', 'manage_options', 'ef-attorneys',  [ $this, 'page_attorneys' ] );
        add_submenu_page( 'ef-attorneys', 'Add Attorney',   'Add Attorney',  'manage_options', 'ef-add',        [ $this, 'page_edit_attorney' ] );
        add_submenu_page( 'ef-attorneys', 'Office Locations','Offices',      'manage_options', 'ef-offices',    [ $this, 'page_offices' ] );
        add_submenu_page( 'ef-attorneys', 'Appearance',     'Appearance',    'manage_options', 'ef-appearance', [ $this, 'page_appearance' ] );
        add_submenu_page( 'ef-attorneys', 'Shortcode Help', 'Shortcode',     'manage_options', 'ef-shortcode',  [ $this, 'page_shortcode' ] );
    }

    /* ── Assets ── */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'ef-' ) === false && strpos( $hook, 'page_ef' ) === false ) return;

        wp_enqueue_media();
        wp_enqueue_style(  'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_style(  'ef-admin', EF_URL . 'assets/css/admin.css', [], EF_VERSION );
        wp_enqueue_script( 'ef-admin', EF_URL . 'assets/js/admin.js',   [ 'jquery', 'wp-color-picker', 'jquery-ui-sortable' ], EF_VERSION, true );
        wp_localize_script( 'ef-admin', 'EF', [
            'nonce'   => wp_create_nonce( 'ef_nonce' ),
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ] );
    }

    /* ── Plugin links ── */
    public function plugin_links( $links ) {
        array_unshift( $links, '<a href="' . admin_url( 'admin.php?page=ef-attorneys' ) . '">Manage</a>' );
        array_unshift( $links, '<a href="' . admin_url( 'admin.php?page=ef-appearance' ) . '">Appearance</a>' );
        return $links;
    }

    /* ════════════════════════════════════════════════════════════
     * PAGE: All Attorneys
     * ════════════════════════════════════════════════════════════ */
    public function page_attorneys() {
        $attorneys = EF_DB::get_attorneys();
        ?>
        <div class="ef-wrap">
            <?php $this->admin_header( 'All Attorneys', 'Drag to reorder. Click Edit to manage combos and photo.' ); ?>

            <div class="ef-toolbar">
                <a href="<?php echo admin_url('admin.php?page=ef-add'); ?>" class="ef-btn ef-btn-primary">
                    + Add Attorney
                </a>
                <span class="ef-count"><?php echo count($attorneys); ?> attorneys</span>
            </div>

            <div class="ef-card">
                <table class="ef-table" id="ef-attorneys-table">
                    <thead>
                        <tr>
                            <th class="col-drag"></th>
                            <th class="col-photo">Photo</th>
                            <th>Name</th>
                            <th>Title</th>
                            <th>Practices</th>
                            <th>States</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ef-sortable">
                    <?php foreach ( $attorneys as $att ) :
                        $combos    = EF_DB::get_combos( $att->id );
                        $practices = array_unique( array_column( (array) $combos, 'practice' ) );
                        $states    = array_unique( array_column( (array) $combos, 'state' ) );
                    ?>
                        <tr data-id="<?php echo esc_attr( $att->id ); ?>">
                            <td class="col-drag"><span class="ef-drag-handle dashicons dashicons-move"></span></td>
                            <td class="col-photo">
                                <?php if ( $att->image_url ) : ?>
                                    <img src="<?php echo esc_url($att->image_url); ?>" class="ef-thumb" alt="">
                                <?php else : ?>
                                    <div class="ef-initials-thumb"><?php echo esc_html( strtoupper( substr($att->name,0,2) ) ); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo esc_html($att->name); ?></strong></td>
                            <td><?php echo esc_html($att->title); ?></td>
                            <td><span class="ef-badge"><?php echo count($practices); ?> areas</span></td>
                            <td><span class="ef-badge ef-badge-blue"><?php echo count($states); ?> states</span></td>
                            <td>
                                <span class="ef-status <?php echo $att->active ? 'ef-status-active' : 'ef-status-inactive'; ?>">
                                    <?php echo $att->active ? 'Active' : 'Hidden'; ?>
                                </span>
                            </td>
                            <td class="col-actions">
                                <a href="<?php echo admin_url('admin.php?page=ef-add&id=' . $att->id); ?>" class="ef-link">Edit</a>
                                <button class="ef-link ef-link-danger ef-delete-attorney" data-id="<?php echo esc_attr($att->id); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /* ════════════════════════════════════════════════════════════
     * PAGE: Add / Edit Attorney
     * ════════════════════════════════════════════════════════════ */
    public function page_edit_attorney() {
        $id  = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : null;
        $att = $id ? EF_DB::get_attorney( $id ) : null;
        $combos = $id ? EF_DB::get_combos( $id ) : [];
        $practices_list = EF_DB::get_distinct_practices();
        $states_list    = EF_DB::get_distinct_states();
        ?>
        <div class="ef-wrap">
            <?php $this->admin_header( $att ? 'Edit Attorney' : 'Add Attorney', $att ? esc_html($att->name) : 'Fill in the details below' ); ?>

            <form id="ef-attorney-form" class="ef-form-grid">
                <?php if ($att) : ?><input type="hidden" name="id" value="<?php echo esc_attr($att->id); ?>"><?php endif; ?>

                <div class="ef-form-main">
                    <!-- Basic Info -->
                    <div class="ef-card">
                        <h3 class="ef-card-title">Basic Information</h3>
                        <div class="ef-fields-grid">
                            <div class="ef-field">
                                <label>Full Name <span class="req">*</span></label>
                                <input type="text" name="name" value="<?php echo esc_attr($att->name ?? ''); ?>" required placeholder="e.g. Santosh Kumar">
                            </div>
                            <div class="ef-field">
                                <label>Title / Role</label>
                                <input type="text" name="title" value="<?php echo esc_attr($att->title ?? ''); ?>" placeholder="e.g. Managing Attorney">
                            </div>
                            <div class="ef-field">
                                <label>Email</label>
                                <input type="email" name="email" value="<?php echo esc_attr($att->email ?? ''); ?>" placeholder="attorney@firm.com">
                            </div>
                            <div class="ef-field">
                                <label>Phone</label>
                                <input type="text" name="phone" value="<?php echo esc_attr($att->phone ?? ''); ?>" placeholder="+1 (555) 000-0000">
                            </div>
                            <div class="ef-field ef-field-full">
                                <label>LinkedIn URL</label>
                                <input type="url" name="linkedin" value="<?php echo esc_attr($att->linkedin ?? ''); ?>" placeholder="https://linkedin.com/in/...">
                            </div>
                            <div class="ef-field ef-field-full">
                                <label>Bio / Description</label>
                                <textarea name="bio" rows="4" placeholder="Short attorney biography..."><?php echo esc_textarea($att->bio ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Practice + State Combinations -->
                    <div class="ef-card">
                        <h3 class="ef-card-title">Practice Area + Bar Admission Combinations
                            <span class="ef-card-hint">Each row = one exact match. Only these combos will show up in filter results.</span>
                        </h3>
                        <div id="ef-combos-list">
                            <?php foreach ( $combos as $combo ) : ?>
                            <div class="ef-combo-row">
                                <select name="combos[practice][]" class="ef-combo-practice">
                                    <option value="">— Practice Area —</option>
                                    <?php foreach ( $practices_list as $p ) : ?>
                                        <option value="<?php echo esc_attr($p); ?>" <?php selected($combo->practice, $p); ?>><?php echo esc_html($p); ?></option>
                                    <?php endforeach; ?>
                                    <option value="__custom__">+ Add custom…</option>
                                </select>
                                <select name="combos[state][]" class="ef-combo-state">
                                    <option value="">— State —</option>
                                    <?php foreach ( $states_list as $s ) : ?>
                                        <option value="<?php echo esc_attr($s); ?>" <?php selected($combo->state, $s); ?>><?php echo esc_html($s); ?></option>
                                    <?php endforeach; ?>
                                    <option value="__custom__">+ Add custom…</option>
                                </select>
                                <button type="button" class="ef-remove-combo ef-btn-icon" title="Remove">&#10005;</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" id="ef-add-combo" class="ef-btn ef-btn-outline">+ Add Combination</button>
                        <p class="ef-hint">Tip: To add a practice across all states, add one row per state.</p>

                        <!-- Hidden template -->
                        <template id="ef-combo-template">
                            <div class="ef-combo-row">
                                <select name="combos[practice][]" class="ef-combo-practice">
                                    <option value="">— Practice Area —</option>
                                    <?php foreach ( $practices_list as $p ) : ?>
                                        <option value="<?php echo esc_attr($p); ?>"><?php echo esc_html($p); ?></option>
                                    <?php endforeach; ?>
                                    <option value="__custom__">+ Add custom…</option>
                                </select>
                                <select name="combos[state][]" class="ef-combo-state">
                                    <option value="">— State —</option>
                                    <?php foreach ( $states_list as $s ) : ?>
                                        <option value="<?php echo esc_attr($s); ?>"><?php echo esc_html($s); ?></option>
                                    <?php endforeach; ?>
                                    <option value="__custom__">+ Add custom…</option>
                                </select>
                                <button type="button" class="ef-remove-combo ef-btn-icon" title="Remove">&#10005;</button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="ef-form-sidebar">
                    <!-- Photo -->
                    <div class="ef-card">
                        <h3 class="ef-card-title">Photo</h3>
                        <div class="ef-photo-preview" id="ef-photo-preview">
                            <?php if ( !empty($att->image_url) ) : ?>
                                <img src="<?php echo esc_url($att->image_url); ?>" id="ef-photo-img" alt="">
                            <?php else : ?>
                                <div class="ef-photo-placeholder" id="ef-photo-img">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <p>No photo yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="image_url" id="ef-image-url" value="<?php echo esc_attr($att->image_url ?? ''); ?>">
                        <button type="button" id="ef-upload-photo" class="ef-btn ef-btn-outline ef-btn-full">Upload Photo</button>
                        <?php if ( !empty($att->image_url) ) : ?>
                            <button type="button" id="ef-remove-photo" class="ef-btn ef-btn-ghost ef-btn-full">Remove Photo</button>
                        <?php endif; ?>
                        <p class="ef-hint">Recommended: 400×500px, JPG or PNG</p>
                    </div>

                    <!-- Status -->
                    <div class="ef-card">
                        <h3 class="ef-card-title">Status</h3>
                        <label class="ef-toggle">
                            <input type="checkbox" name="active" value="1" <?php checked( $att->active ?? 1, 1 ); ?>>
                            <span class="ef-toggle-slider"></span>
                            <span class="ef-toggle-label">Show on website</span>
                        </label>
                        <div class="ef-field" style="margin-top:16px">
                            <label>Sort Order</label>
                            <input type="number" name="sort_order" value="<?php echo esc_attr($att->sort_order ?? 0); ?>" min="0" style="width:80px">
                        </div>
                    </div>

                    <!-- Save -->
                    <div class="ef-card ef-card-actions">
                        <button type="submit" class="ef-btn ef-btn-primary ef-btn-full ef-btn-lg" id="ef-save-btn">
                            <span class="ef-btn-text">Save Attorney</span>
                            <span class="ef-spinner" style="display:none"></span>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=ef-attorneys'); ?>" class="ef-btn ef-btn-ghost ef-btn-full">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /* ════════════════════════════════════════════════════════════
     * PAGE: Offices
     * ════════════════════════════════════════════════════════════ */
    public function page_offices() {
        $offices = EF_DB::get_offices();
        ?>
        <div class="ef-wrap">
            <?php $this->admin_header( 'Office Locations', 'All attorneys are shown for every office location.' ); ?>
            <div class="ef-two-col">
                <!-- Office list -->
                <div class="ef-card">
                    <h3 class="ef-card-title">Current Offices</h3>
                    <table class="ef-table">
                        <thead><tr><th>Office Name</th><th>Address</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ( $offices as $o ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($o->name); ?></strong></td>
                                <td><?php echo esc_html($o->address); ?></td>
                                <td><span class="ef-status <?php echo $o->active ? 'ef-status-active' : 'ef-status-inactive'; ?>"><?php echo $o->active ? 'Active' : 'Hidden'; ?></span></td>
                                <td class="col-actions">
                                    <button class="ef-link ef-edit-office"
                                        data-id="<?php echo esc_attr($o->id); ?>"
                                        data-name="<?php echo esc_attr($o->name); ?>"
                                        data-address="<?php echo esc_attr($o->address); ?>"
                                        data-active="<?php echo esc_attr($o->active); ?>">Edit</button>
                                    <button class="ef-link ef-link-danger ef-delete-office" data-id="<?php echo esc_attr($o->id); ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Add/Edit office form -->
                <div class="ef-card">
                    <h3 class="ef-card-title" id="ef-office-form-title">Add Office</h3>
                    <form id="ef-office-form">
                        <input type="hidden" id="ef-office-id" name="id" value="">
                        <div class="ef-field">
                            <label>Office Name <span class="req">*</span></label>
                            <input type="text" id="ef-office-name" name="name" placeholder="e.g. Ashburn, VA" required>
                        </div>
                        <div class="ef-field">
                            <label>Full Address</label>
                            <input type="text" id="ef-office-address" name="address" placeholder="123 Main St, Ashburn, VA 20147">
                        </div>
                        <label class="ef-toggle" style="margin:16px 0">
                            <input type="checkbox" id="ef-office-active" name="active" value="1" checked>
                            <span class="ef-toggle-slider"></span>
                            <span class="ef-toggle-label">Show on website</span>
                        </label>
                        <div style="display:flex;gap:8px;margin-top:16px">
                            <button type="submit" class="ef-btn ef-btn-primary">Save Office</button>
                            <button type="button" id="ef-office-cancel" class="ef-btn ef-btn-ghost" style="display:none">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /* ════════════════════════════════════════════════════════════
     * PAGE: Appearance
     * ════════════════════════════════════════════════════════════ */
    public function page_appearance() {
        $s = EF_DB::get_settings();
        $fonts = [ 'Cormorant Garamond', 'Playfair Display', 'Merriweather', 'Lora', 'EB Garamond', 'Libre Baskerville' ];
        $body_fonts = [ 'DM Sans', 'Nunito', 'Lato', 'Source Sans Pro', 'Open Sans', 'Raleway' ];
        ?>
        <div class="ef-wrap">
            <?php $this->admin_header( 'Appearance', 'Customize colors, fonts, layout and visibility settings.' ); ?>

            <form id="ef-settings-form" class="ef-form-grid">
                <div class="ef-form-main">

                    <!-- Colors -->
                    <div class="ef-card">
                        <h3 class="ef-card-title">🎨 Colors</h3>
                        <div class="ef-fields-grid ef-fields-grid-3">
                            <?php
                            $color_fields = [
                                'primary_color'    => [ 'Primary Color',    'Header background, buttons, badges' ],
                                'accent_color'     => [ 'Accent Color',     'Gold highlights, tags, borders' ],
                                'background_color' => [ 'Page Background',  'Page background color' ],
                                'card_bg_color'    => [ 'Card Background',  'Attorney card background' ],
                                'text_color'       => [ 'Text Color',       'Main body text color' ],
                            ];
                            foreach ( $color_fields as $key => [$label, $desc] ) : ?>
                            <div class="ef-field">
                                <label><?php echo esc_html($label); ?></label>
                                <div class="ef-color-wrap">
                                    <input type="text" name="<?php echo esc_attr($key); ?>"
                                           value="<?php echo esc_attr($s[$key]); ?>"
                                           class="ef-color-picker"
                                           data-default-color="<?php echo esc_attr($s[$key]); ?>">
                                </div>
                                <p class="ef-hint"><?php echo esc_html($desc); ?></p>
                            </div>
                            <?php endforeach; ?>
                            <div class="ef-field">
                                <label>Border Radius (px)</label>
                                <div class="ef-range-wrap">
                                    <input type="range" name="border_radius" min="0" max="24" value="<?php echo esc_attr($s['border_radius']); ?>" class="ef-range" id="ef-radius-range">
                                    <span id="ef-radius-val"><?php echo esc_attr($s['border_radius']); ?>px</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Typography -->
                    <div class="ef-card">
                        <h3 class="ef-card-title">✏️ Typography</h3>
                        <div class="ef-fields-grid">
                            <div class="ef-field">
                                <label>Display Font (headings, names)</label>
                                <select name="display_font">
                                    <?php foreach ( $fonts as $f ) : ?>
                                        <option value="<?php echo esc_attr($f); ?>" <?php selected($s['display_font'], $f); ?>><?php echo esc_html($f); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="ef-field">
                                <label>Body Font (labels, text)</label>
                                <select name="body_font">
                                    <?php foreach ( $body_fonts as $f ) : ?>
                                        <option value="<?php echo esc_attr($f); ?>" <?php selected($s['body_font'], $f); ?>><?php echo esc_html($f); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Header -->
                    <div class="ef-card">
                        <h3 class="ef-card-title">🏷 Header Text</h3>
                        <label class="ef-toggle" style="margin-bottom:16px">
                            <input type="checkbox" name="show_header" value="1" <?php checked($s['show_header'], '1'); ?>>
                            <span class="ef-toggle-slider"></span>
                            <span class="ef-toggle-label">Show header section</span>
                        </label>
                        <div class="ef-fields-grid">
                            <div class="ef-field">
                                <label>Title</label>
                                <input type="text" name="header_title" value="<?php echo esc_attr($s['header_title']); ?>">
                            </div>
                            <div class="ef-field">
                                <label>Subtitle</label>
                                <input type="text" name="header_subtitle" value="<?php echo esc_attr($s['header_subtitle']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Layout -->
                    <div class="ef-card">
                        <h3 class="ef-card-title">📐 Layout</h3>
                        <div class="ef-fields-grid ef-fields-grid-3">
                            <div class="ef-field">
                                <label>Cards Per Row</label>
                                <select name="cards_per_row">
                                    <?php foreach ([2,3,4] as $n) : ?>
                                        <option value="<?php echo $n; ?>" <?php selected($s['cards_per_row'], $n); ?>><?php echo $n; ?> columns</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="ef-field">
                                <label>Card Style</label>
                                <select name="card_style">
                                    <option value="photo"    <?php selected($s['card_style'],'photo'); ?>>Photo / Initials Avatar</option>
                                    <option value="minimal"  <?php selected($s['card_style'],'minimal'); ?>>Minimal (no avatar)</option>
                                    <option value="bordered" <?php selected($s['card_style'],'bordered'); ?>>Bordered / Outlined</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Visibility -->
                    <div class="ef-card">
                        <h3 class="ef-card-title">👁 Show / Hide Fields</h3>
                        <div class="ef-toggles-grid">
                            <?php
                            $toggles = [
                                'show_bio'             => 'Bio / Description',
                                'show_email'           => 'Email',
                                'show_phone'           => 'Phone Number',
                                'show_linkedin'        => 'LinkedIn Link',
                                'show_practices'       => 'Practice Area Tags',
                                'show_states'          => 'State Count Badge',
                                'show_office_pills'    => 'Office Location Pills',
                                'show_search'          => 'Name Search Filter',
                                'show_practice_filter' => 'Practice Area Filter',
                                'show_state_filter'    => 'State / Bar Admission Filter',
                                'show_office_filter'   => 'Office Location Filter',
                                'show_view_toggle'     => 'Grid / List View Toggle',
                            ];
                            foreach ( $toggles as $key => $label ) : ?>
                            <label class="ef-toggle">
                                <input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1" <?php checked($s[$key], '1'); ?>>
                                <span class="ef-toggle-slider"></span>
                                <span class="ef-toggle-label"><?php echo esc_html($label); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Custom CSS -->
                    <div class="ef-card">
                        <h3 class="ef-card-title">💻 Custom CSS</h3>
                        <p class="ef-hint" style="margin-bottom:12px">Advanced: add your own CSS overrides. All selectors are scoped to <code>#ef-root</code>.</p>
                        <textarea name="custom_css" rows="10" class="ef-code" placeholder="/* e.g. */&#10;#ef-root .ef-card { box-shadow: none; }&#10;#ef-root .ef-name { font-size: 1.5rem; }"><?php echo esc_textarea($s['custom_css']); ?></textarea>
                    </div>

                </div>

                <!-- Sidebar -->
                <div class="ef-form-sidebar">
                    <div class="ef-card ef-card-actions">
                        <button type="submit" class="ef-btn ef-btn-primary ef-btn-full ef-btn-lg" id="ef-settings-save">
                            <span class="ef-btn-text">Save Settings</span>
                            <span class="ef-spinner" style="display:none"></span>
                        </button>
                        <button type="button" id="ef-settings-reset" class="ef-btn ef-btn-ghost ef-btn-full">Reset to Defaults</button>
                    </div>

                    <!-- Live preview hint -->
                    <div class="ef-card ef-preview-hint">
                        <h4>💡 Shortcode</h4>
                        <code>[everything_filter]</code>
                        <p class="ef-hint" style="margin-top:8px">Add this to any page to display the directory with your settings applied.</p>
                    </div>

                    <!-- Color presets -->
                    <div class="ef-card">
                        <h4 style="margin-bottom:12px;font-size:.9rem;font-weight:600">Quick Color Presets</h4>
                        <div class="ef-presets">
                            <?php
                            $presets = [
                                [ 'Navy & Gold',   '#0d2340', '#c9a84c', '#f8f5f0' ],
                                [ 'Forest & Cream','#1a3d2b', '#d4a853', '#f5f0e8' ],
                                [ 'Slate & Rose',  '#2d3561', '#e8789a', '#f9f7fb' ],
                                [ 'Charcoal',      '#1c1c1e', '#f0a500', '#f4f4f4' ],
                                [ 'Deep Teal',     '#0a3d4a', '#7ecac3', '#f0f7f7' ],
                                [ 'Burgundy',      '#4a1328', '#d4a04a', '#faf5f0' ],
                            ];
                            foreach ( $presets as $preset ) : ?>
                            <button type="button" class="ef-preset"
                                    data-primary="<?php echo esc_attr($preset[1]); ?>"
                                    data-accent="<?php echo esc_attr($preset[2]); ?>"
                                    data-bg="<?php echo esc_attr($preset[3]); ?>"
                                    title="<?php echo esc_attr($preset[0]); ?>">
                                <span style="background:<?php echo esc_attr($preset[1]); ?>"></span>
                                <span style="background:<?php echo esc_attr($preset[2]); ?>"></span>
                                <span style="background:<?php echo esc_attr($preset[3]); ?>"></span>
                                <small><?php echo esc_html($preset[0]); ?></small>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /* ════════════════════════════════════════════════════════════
     * PAGE: Shortcode Help
     * ════════════════════════════════════════════════════════════ */
    public function page_shortcode() {
        ?>
        <div class="ef-wrap">
            <?php $this->admin_header( 'Shortcode Usage', 'How to embed the attorney directory on your site.' ); ?>
            <div class="ef-card" style="max-width:700px">
                <h3 class="ef-card-title">Basic Usage</h3>
                <div class="ef-code-block"><code>[everything_filter]</code></div>
                <p class="ef-hint">Paste this shortcode into any page or post.</p>

                <h3 class="ef-card-title" style="margin-top:28px">Custom Title</h3>
                <div class="ef-code-block"><code>[everything_filter title="Meet Our Team" subtitle="Find the right attorney for you"]</code></div>

                <h3 class="ef-card-title" style="margin-top:28px">Where to add it</h3>
                <ol style="padding-left:20px;line-height:2">
                    <li>Go to <strong>Pages → Add New</strong> (or edit an existing page)</li>
                    <li>Add a <strong>Shortcode block</strong> or <strong>Custom HTML block</strong></li>
                    <li>Paste <code>[everything_filter]</code></li>
                    <li>Publish / Update the page</li>
                </ol>

                <h3 class="ef-card-title" style="margin-top:28px">Elementor Users</h3>
                <p>Search for the <strong>Shortcode</strong> widget in Elementor and paste <code>[everything_filter]</code> inside it.</p>
            </div>
        </div>
        <?php
    }

    /* ── Shared header ── */
    private function admin_header( $title, $subtitle = '' ) {
        ?>
        <div class="ef-page-header">
            <div class="ef-page-header-left">
                <h1 class="ef-page-title"><?php echo esc_html($title); ?></h1>
                <?php if ($subtitle) : ?><p class="ef-page-sub"><?php echo esc_html($subtitle); ?></p><?php endif; ?>
            </div>
            <div class="ef-page-header-right">
                <span class="ef-version-badge">v<?php echo EF_VERSION; ?></span>
            </div>
        </div>
        <?php
    }

    /* ════════════════════════════════════════════════════════════
     * AJAX HANDLERS
     * ════════════════════════════════════════════════════════════ */
    private function verify_nonce() {
        if ( ! check_ajax_referer( 'ef_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Security check failed.' ] );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
        }
    }

    public function ajax_save_attorney() {
        $this->verify_nonce();
        $id  = ! empty( $_POST['id'] ) ? intval( $_POST['id'] ) : null;
        $new_id = EF_DB::save_attorney( $_POST, $id );

        /* Save combos */
        $practices = $_POST['combos']['practice'] ?? [];
        $states    = $_POST['combos']['state']    ?? [];
        $combos    = [];
        foreach ( $practices as $i => $p ) {
            if ( $p && isset($states[$i]) && $states[$i] ) {
                $combos[] = [ 'practice' => $p, 'state' => $states[$i] ];
            }
        }
        EF_DB::save_combos( $new_id, $combos );

        wp_send_json_success( [
            'message' => 'Attorney saved successfully.',
            'id'      => $new_id,
            'redirect'=> admin_url( 'admin.php?page=ef-attorneys' ),
        ] );
    }

    public function ajax_delete_attorney() {
        $this->verify_nonce();
        $id = intval( $_POST['id'] );
        EF_DB::delete_attorney( $id );
        wp_send_json_success( [ 'message' => 'Attorney deleted.' ] );
    }

    public function ajax_save_combos() {
        $this->verify_nonce();
        $attorney_id = intval( $_POST['attorney_id'] );
        $combos      = $_POST['combos'] ?? [];
        EF_DB::save_combos( $attorney_id, $combos );
        wp_send_json_success( [ 'message' => 'Combinations saved.' ] );
    }

    public function ajax_save_office() {
        $this->verify_nonce();
        $id = ! empty( $_POST['id'] ) ? intval( $_POST['id'] ) : null;
        EF_DB::save_office( $_POST, $id );
        wp_send_json_success( [ 'message' => 'Office saved.' ] );
    }

    public function ajax_delete_office() {
        $this->verify_nonce();
        EF_DB::delete_office( intval( $_POST['id'] ) );
        wp_send_json_success( [ 'message' => 'Office deleted.' ] );
    }

    public function ajax_save_settings() {
        $this->verify_nonce();
        EF_DB::save_settings( $_POST );
        wp_send_json_success( [ 'message' => 'Settings saved.' ] );
    }

    public function ajax_reorder_attorneys() {
        $this->verify_nonce();
        global $wpdb;
        $order = $_POST['order'] ?? [];
        foreach ( $order as $i => $id ) {
            $wpdb->update( EF_DB::attorneys_table(), [ 'sort_order' => $i ], [ 'id' => intval($id) ] );
        }
        wp_send_json_success();
    }
}
