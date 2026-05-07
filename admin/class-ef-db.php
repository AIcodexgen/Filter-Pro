<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AFP_DB {

    /* ── Table names ── */
    public static function attorneys_table()    { global $wpdb; return $wpdb->prefix . 'afp_attorneys'; }
    public static function combos_table()       { global $wpdb; return $wpdb->prefix . 'afp_combinations'; }
    public static function offices_table()      { global $wpdb; return $wpdb->prefix . 'afp_offices'; }
    public static function settings_option()    { return 'afp_settings'; }

    /* ── Install / create tables ── */
    public static function install() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( "CREATE TABLE " . self::attorneys_table() . " (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name         VARCHAR(120) NOT NULL,
            title        VARCHAR(120) DEFAULT '',
            bio          TEXT DEFAULT '',
            email        VARCHAR(120) DEFAULT '',
            phone        VARCHAR(60)  DEFAULT '',
            linkedin     VARCHAR(255) DEFAULT '',
            image_url    VARCHAR(500) DEFAULT '',
            sort_order   INT DEFAULT 0,
            active       TINYINT(1) DEFAULT 1,
            created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        dbDelta( "CREATE TABLE " . self::combos_table() . " (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            attorney_id  INT UNSIGNED NOT NULL,
            practice     VARCHAR(120) NOT NULL,
            state        VARCHAR(120) NOT NULL,
            PRIMARY KEY (id),
            KEY attorney_id (attorney_id)
        ) $charset;" );

        dbDelta( "CREATE TABLE " . self::offices_table() . " (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name         VARCHAR(120) NOT NULL,
            address      VARCHAR(255) DEFAULT '',
            sort_order   INT DEFAULT 0,
            active       TINYINT(1) DEFAULT 1,
            PRIMARY KEY (id)
        ) $charset;" );

        /* Seed default data if tables are empty */
        self::maybe_seed();
        update_option( 'afp_db_version', AFP_VERSION );
    }

    public static function deactivate() {}

    /* ── Seed default data ── */
    private static function maybe_seed() {
        global $wpdb;
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM " . self::attorneys_table() );
        if ( $count > 0 ) return;

        /* Default offices */
        $offices = [ 'Ashburn, VA', 'Dallas, TX', 'Princeton, NJ' ];
        foreach ( $offices as $i => $o ) {
            $wpdb->insert( self::offices_table(), [ 'name' => $o, 'sort_order' => $i ] );
        }

        /* Default attorneys + combos */
        $default_attorneys = [
            [ 'Elizabeth',    'Senior Attorney',       '' ],
            [ 'Jibran',       'Attorney',              '' ],
            [ 'Kiran',        'Attorney',              '' ],
            [ 'Sai',          'Attorney',              '' ],
            [ 'Samyuktha',    'Attorney',              '' ],
            [ 'Sangeetha',    'Immigration Specialist','' ],
            [ 'Santosh',      'Managing Attorney',     '' ],
            [ 'Steve',        'Attorney',              '' ],
            [ 'Sumyuktha',    'Attorney',              '' ],
            [ 'Vijay',        'Attorney',              '' ],
            [ 'Yahya',        'Attorney',              '' ],
            [ 'Yahya Thabit', 'Attorney',              '' ],
        ];

        $att_ids = [];
        foreach ( $default_attorneys as $i => $a ) {
            $wpdb->insert( self::attorneys_table(), [
                'name'       => $a[0],
                'title'      => $a[1],
                'sort_order' => $i,
            ] );
            $att_ids[ $a[0] ] = $wpdb->insert_id;
        }

        /* Raw combos [ attorney_name, practice, state ] */
        $combos = [
            ["Elizabeth","Corporate","Texas"],["Elizabeth","Employment","Texas"],["Elizabeth","Litigation","Texas"],
            ["Jibran","Criminal Law","Virginia"],["Jibran","Family Law","Virginia"],
            ["Kiran","Estate Planning","Arizona"],["Kiran","Estate Planning","District Of Columbia"],
            ["Kiran","Estate Planning","Georgia"],["Kiran","Estate Planning","Illinois"],
            ["Kiran","Estate Planning","Maryland"],["Kiran","Estate Planning","Michigan"],
            ["Kiran","Estate Planning","North Carolina"],["Kiran","Estate Planning","Ohio"],
            ["Kiran","Estate Planning","Virginia"],["Kiran","Estate Planning","Washington"],
            ["Sai","Corporate","Indiana"],["Sai","Criminal Law","Indiana"],["Sai","Employment","Indiana"],
            ["Sai","Estate Planning","Indiana"],["Sai","Family Law","Indiana"],
            ["Sai","Litigation","Indiana"],["Sai","Real Estate","Indiana"],
            ["Samyuktha","Corporate","Missouri"],["Samyuktha","Criminal Law","Missouri"],
            ["Samyuktha","Employment","Missouri"],["Samyuktha","Estate Planning","Missouri"],
            ["Samyuktha","Family Law","Missouri"],["Samyuktha","Litigation","Missouri"],
            ["Samyuktha","Real Estate","Missouri"],
            ["Sangeetha","Immigration","Arizona"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","Arizona"],
            ["Sangeetha","Immigration","District Of Columbia"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","District Of Columbia"],
            ["Sangeetha","Immigration","Georgia"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","Georgia"],
            ["Sangeetha","Immigration","Illinois"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","Illinois"],
            ["Sangeetha","Immigration","Indiana"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","Indiana"],
            ["Sangeetha","Immigration","Maryland"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","Maryland"],
            ["Sangeetha","Immigration","Michigan"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","Michigan"],
            ["Sangeetha","Immigration","Missouri"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","Missouri"],
            ["Sangeetha","Immigration","New Jersey"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","New Jersey"],
            ["Sangeetha","Immigration","New York"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","New York"],
            ["Sangeetha","Immigration","North Carolina"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","North Carolina"],
            ["Sangeetha","Immigration","Ohio"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","Ohio"],
            ["Sangeetha","Immigration","Pennsylvania"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","Pennsylvania"],
            ["Sangeetha","Immigration","Texas"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","Texas"],
            ["Sangeetha","Immigration","Virginia"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","Virginia"],
            ["Sangeetha","Immigration","Washington"],["Sangeetha","Immigration (EB5, NIW, EB1, O1, P3)","Washington"],
            ["Santosh","Corporate","Arizona"],["Santosh","Criminal Law","Arizona"],["Santosh","Employment","Arizona"],
            ["Santosh","Estate Planning","Arizona"],["Santosh","Family Law","Arizona"],["Santosh","Litigation","Arizona"],
            ["Santosh","Corporate","District Of Columbia"],["Santosh","Criminal Law","District Of Columbia"],
            ["Santosh","Employment","District Of Columbia"],["Santosh","Estate Planning","District Of Columbia"],
            ["Santosh","Family Law","District Of Columbia"],["Santosh","Litigation","District Of Columbia"],
            ["Santosh","Real Estate","District Of Columbia"],
            ["Santosh","Corporate","Georgia"],["Santosh","Criminal Law","Georgia"],["Santosh","Employment","Georgia"],
            ["Santosh","Estate Planning","Georgia"],["Santosh","Family Law","Georgia"],["Santosh","Litigation","Georgia"],
            ["Santosh","Corporate","Illinois"],["Santosh","Criminal Law","Illinois"],["Santosh","Employment","Illinois"],
            ["Santosh","Estate Planning","Illinois"],["Santosh","Family Law","Illinois"],["Santosh","Litigation","Illinois"],
            ["Santosh","Corporate","Maryland"],["Santosh","Criminal Law","Maryland"],["Santosh","Employment","Maryland"],
            ["Santosh","Estate Planning","Maryland"],["Santosh","Family Law","Maryland"],["Santosh","Litigation","Maryland"],
            ["Santosh","Corporate","Michigan"],["Santosh","Criminal Law","Michigan"],["Santosh","Employment","Michigan"],
            ["Santosh","Estate Planning","Michigan"],["Santosh","Family Law","Michigan"],["Santosh","Litigation","Michigan"],
            ["Santosh","Corporate","North Carolina"],["Santosh","Criminal Law","North Carolina"],
            ["Santosh","Employment","North Carolina"],["Santosh","Estate Planning","North Carolina"],
            ["Santosh","Family Law","North Carolina"],["Santosh","Litigation","North Carolina"],
            ["Santosh","Corporate","Ohio"],["Santosh","Criminal Law","Ohio"],["Santosh","Employment","Ohio"],
            ["Santosh","Estate Planning","Ohio"],["Santosh","Family Law","Ohio"],["Santosh","Litigation","Ohio"],
            ["Santosh","Real Estate","Ohio"],
            ["Santosh","Corporate","Texas"],["Santosh","Employment","Texas"],["Santosh","Estate Planning","Texas"],
            ["Santosh","Family Law","Texas"],["Santosh","Litigation","Texas"],
            ["Santosh","Corporate","Virginia"],["Santosh","Employment","Virginia"],
            ["Santosh","Estate Planning","Virginia"],["Santosh","Litigation","Virginia"],
            ["Santosh","Corporate","Washington"],["Santosh","Criminal Law","Washington"],
            ["Santosh","Employment","Washington"],["Santosh","Estate Planning","Washington"],
            ["Santosh","Family Law","Washington"],["Santosh","Litigation","Washington"],
            ["Santosh","Immigration","Arizona"],["Santosh","Immigration","District Of Columbia"],
            ["Santosh","Immigration","Georgia"],["Santosh","Immigration","Illinois"],
            ["Santosh","Immigration","Indiana"],["Santosh","Immigration","Maryland"],
            ["Santosh","Immigration","Michigan"],["Santosh","Immigration","Missouri"],
            ["Santosh","Immigration","New Jersey"],["Santosh","Immigration","New York"],
            ["Santosh","Immigration","North Carolina"],["Santosh","Immigration","Ohio"],
            ["Santosh","Immigration","Pennsylvania"],["Santosh","Immigration","Texas"],
            ["Santosh","Immigration","Virginia"],["Santosh","Immigration","Washington"],
            ["Steve","Criminal Law","Texas"],
            ["Sumyuktha","Immigration","Arizona"],["Sumyuktha","Immigration","District Of Columbia"],
            ["Sumyuktha","Immigration","Georgia"],["Sumyuktha","Immigration","Illinois"],
            ["Sumyuktha","Immigration","Indiana"],["Sumyuktha","Immigration","Maryland"],
            ["Sumyuktha","Immigration","Michigan"],["Sumyuktha","Immigration","Missouri"],
            ["Sumyuktha","Immigration","New Jersey"],["Sumyuktha","Immigration","New York"],
            ["Sumyuktha","Immigration","North Carolina"],["Sumyuktha","Immigration","Ohio"],
            ["Sumyuktha","Immigration","Pennsylvania"],["Sumyuktha","Immigration","Texas"],
            ["Sumyuktha","Immigration","Virginia"],["Sumyuktha","Immigration","Washington"],
            ["Vijay","Corporate","New Jersey"],["Vijay","Criminal Law","New Jersey"],
            ["Vijay","Employment","New Jersey"],["Vijay","Estate Planning","New Jersey"],
            ["Vijay","Family Law","New Jersey"],["Vijay","Litigation","New Jersey"],["Vijay","Real Estate","New Jersey"],
            ["Vijay","Corporate","New York"],["Vijay","Criminal Law","New York"],
            ["Vijay","Employment","New York"],["Vijay","Estate Planning","New York"],
            ["Vijay","Family Law","New York"],["Vijay","Litigation","New York"],["Vijay","Real Estate","New York"],
            ["Vijay","Corporate","Pennsylvania"],["Vijay","Criminal Law","Pennsylvania"],
            ["Vijay","Employment","Pennsylvania"],["Vijay","Estate Planning","Pennsylvania"],
            ["Vijay","Family Law","Pennsylvania"],["Vijay","Litigation","Pennsylvania"],
            ["Vijay","Real Estate","Pennsylvania"],
            ["Yahya","Corporate","Virginia"],["Yahya","Employment","Virginia"],["Yahya","Litigation","Virginia"],
            ["Yahya Thabit","Corporate","Maryland"],["Yahya Thabit","Employment","Maryland"],
            ["Yahya Thabit","Litigation","Maryland"],
        ];

        foreach ( $combos as $c ) {
            $aid = $att_ids[ $c[0] ] ?? null;
            if ( $aid ) {
                $wpdb->insert( self::combos_table(), [
                    'attorney_id' => $aid,
                    'practice'    => $c[1],
                    'state'       => $c[2],
                ] );
            }
        }

        /* Default settings */
        self::save_settings( self::default_settings() );
    }

    /* ── Default settings ── */
    public static function default_settings() {
        return [
            /* Appearance */
            'primary_color'       => '#0d2340',
            'accent_color'        => '#c9a84c',
            'background_color'    => '#f8f5f0',
            'card_bg_color'       => '#ffffff',
            'text_color'          => '#1a1a2e',
            'border_radius'       => '12',
            /* Header */
            'header_title'        => 'Find an Attorney',
            'header_subtitle'     => 'Search by name, practice area, bar admission or office location',
            'show_header'         => '1',
            /* Layout */
            'cards_per_row'       => '3',
            'card_style'          => 'photo',   /* photo | initials | minimal */
            'show_bio'            => '1',
            'show_email'          => '1',
            'show_phone'          => '1',
            'show_linkedin'       => '1',
            'show_practices'      => '1',
            'show_states'         => '1',
            'show_office_pills'   => '1',
            /* Filters */
            'show_search'         => '1',
            'show_practice_filter'=> '1',
            'show_state_filter'   => '1',
            'show_office_filter'  => '1',
            'show_view_toggle'    => '1',
            /* Typography */
            'display_font'        => 'Cormorant Garamond',
            'body_font'           => 'DM Sans',
            /* Custom CSS */
            'custom_css'          => '',
        ];
    }

    /* ── Settings helpers ── */
    public static function get_settings() {
        $saved    = get_option( self::settings_option(), [] );
        $defaults = self::default_settings();
        return wp_parse_args( $saved, $defaults );
    }

    public static function save_settings( $data ) {
        $allowed = array_keys( self::default_settings() );
        $clean   = [];
        foreach ( $allowed as $key ) {
            if ( isset( $data[ $key ] ) ) {
                $clean[ $key ] = sanitize_text_field( $data[ $key ] );
            }
        }
        /* custom_css needs wp_strip_all_tags not sanitize_text_field */
        if ( isset( $data['custom_css'] ) ) {
            $clean['custom_css'] = wp_strip_all_tags( $data['custom_css'] );
        }
        update_option( self::settings_option(), $clean );
    }

    /* ── Attorney CRUD ── */
    public static function get_attorneys( $active_only = false ) {
        global $wpdb;
        $where = $active_only ? "WHERE active = 1" : "";
        return $wpdb->get_results(
            "SELECT * FROM " . self::attorneys_table() . " $where ORDER BY sort_order ASC, name ASC"
        );
    }

    public static function get_attorney( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::attorneys_table() . " WHERE id = %d", $id
        ) );
    }

    public static function save_attorney( $data, $id = null ) {
        global $wpdb;
        $fields = [
            'name'      => sanitize_text_field( $data['name'] ?? '' ),
            'title'     => sanitize_text_field( $data['title'] ?? '' ),
            'bio'       => sanitize_textarea_field( $data['bio'] ?? '' ),
            'email'     => sanitize_email( $data['email'] ?? '' ),
            'phone'     => sanitize_text_field( $data['phone'] ?? '' ),
            'linkedin'  => esc_url_raw( $data['linkedin'] ?? '' ),
            'image_url' => esc_url_raw( $data['image_url'] ?? '' ),
            'sort_order'=> intval( $data['sort_order'] ?? 0 ),
            'active'    => isset( $data['active'] ) ? 1 : 0,
        ];
        if ( $id ) {
            $wpdb->update( self::attorneys_table(), $fields, [ 'id' => $id ] );
            return $id;
        } else {
            $wpdb->insert( self::attorneys_table(), $fields );
            return $wpdb->insert_id;
        }
    }

    public static function delete_attorney( $id ) {
        global $wpdb;
        $wpdb->delete( self::combos_table(),   [ 'attorney_id' => $id ] );
        $wpdb->delete( self::attorneys_table(), [ 'id'         => $id ] );
    }

    /* ── Combination CRUD ── */
    public static function get_combos( $attorney_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . self::combos_table() . " WHERE attorney_id = %d ORDER BY state, practice", $attorney_id
        ) );
    }

    public static function get_all_combos() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT c.*, a.name as attorney_name
             FROM " . self::combos_table() . " c
             JOIN " . self::attorneys_table() . " a ON a.id = c.attorney_id
             WHERE a.active = 1
             ORDER BY a.sort_order, c.state, c.practice"
        );
    }

    public static function save_combos( $attorney_id, $combos ) {
        global $wpdb;
        $wpdb->delete( self::combos_table(), [ 'attorney_id' => $attorney_id ] );
        foreach ( $combos as $combo ) {
            $p = sanitize_text_field( $combo['practice'] ?? '' );
            $s = sanitize_text_field( $combo['state']    ?? '' );
            if ( $p && $s ) {
                $wpdb->insert( self::combos_table(), [
                    'attorney_id' => $attorney_id,
                    'practice'    => $p,
                    'state'       => $s,
                ] );
            }
        }
    }

    public static function delete_combo( $id ) {
        global $wpdb;
        $wpdb->delete( self::combos_table(), [ 'id' => $id ] );
    }

    /* ── Office CRUD ── */
    public static function get_offices( $active_only = false ) {
        global $wpdb;
        $where = $active_only ? "WHERE active = 1" : "";
        return $wpdb->get_results(
            "SELECT * FROM " . self::offices_table() . " $where ORDER BY sort_order ASC, name ASC"
        );
    }

    public static function save_office( $data, $id = null ) {
        global $wpdb;
        $fields = [
            'name'       => sanitize_text_field( $data['name'] ?? '' ),
            'address'    => sanitize_text_field( $data['address'] ?? '' ),
            'sort_order' => intval( $data['sort_order'] ?? 0 ),
            'active'     => isset( $data['active'] ) ? 1 : 0,
        ];
        if ( $id ) {
            $wpdb->update( self::offices_table(), $fields, [ 'id' => $id ] );
        } else {
            $wpdb->insert( self::offices_table(), $fields );
            return $wpdb->insert_id;
        }
        return $id;
    }

    public static function delete_office( $id ) {
        global $wpdb;
        $wpdb->delete( self::offices_table(), [ 'id' => $id ] );
    }

    /* ── Distinct practice areas & states ── */
    public static function get_distinct_practices() {
        global $wpdb;
        return $wpdb->get_col( "SELECT DISTINCT practice FROM " . self::combos_table() . " ORDER BY practice" );
    }

    public static function get_distinct_states() {
        global $wpdb;
        return $wpdb->get_col( "SELECT DISTINCT state FROM " . self::combos_table() . " ORDER BY state" );
    }
}
