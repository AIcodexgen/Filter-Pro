<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AFP_Shortcode {

    /** Tracks whether our shortcode has actually been rendered on this request */
    private static $rendered = false;

    public function __construct() {
        add_shortcode( 'attorney_filter', [ $this, 'render' ] );

        /*
         * Elementor stores widget data in post meta (_elementor_data), NOT in
         * post_content, so has_shortcode( $post->post_content ) always returns
         * false on Elementor pages.  The safest fix is:
         *  1. Always register the shortcode (done above).
         *  2. Enqueue fonts LATE (wp_footer) only when the shortcode was actually
         *     called during this request, instead of trying to predict it upfront.
         *  3. Also hook into Elementor's own init so it processes [attorney_filter]
         *     inside its Shortcode widget correctly.
         */
        add_action( 'wp_footer',          [ $this, 'maybe_enqueue_fonts' ] );

        /* Tell Elementor to treat our shortcode as safe / renderable */
        add_action( 'elementor/widgets/widgets_registered', [ $this, 'elementor_compat' ] );

        /* Elementor sometimes needs do_shortcode applied to its content */
        add_filter( 'elementor/frontend/the_content', 'do_shortcode' );
    }

    /**
     * Called from wp_footer — only outputs the <link> if render() was invoked.
     * Fonts are in <head> ideally but footer is fine for Google Fonts (async).
     */
    public function maybe_enqueue_fonts() {
        if ( ! self::$rendered ) return;
        $s  = AFP_DB::get_settings();
        $df = urlencode( $s['display_font'] );
        $bf = urlencode( $s['body_font'] );
        /* Only print if not already enqueued */
        if ( ! wp_style_is( 'afp-fonts', 'done' ) ) {
            echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . esc_attr($df) . ':wght@400;500;600;700&family=' . esc_attr($bf) . ':wght@300;400;500;600&display=swap">' . "\n";
        }
    }

    /** No-op — just ensures Elementor's widget registry hook fires without error */
    public function elementor_compat() {}

    public function render( $atts ) {
        self::$rendered = true;   /* tells maybe_enqueue_fonts() to fire */

        $atts = shortcode_atts([
            'title'    => '',
            'subtitle' => '',
        ], $atts );

        $s       = AFP_DB::get_settings();
        $combos  = AFP_DB::get_all_combos();
        $offices = AFP_DB::get_offices( true );

        /* Override with shortcode atts */
        if ( $atts['title'] )    $s['header_title']    = $atts['title'];
        if ( $atts['subtitle'] ) $s['header_subtitle']  = $atts['subtitle'];

        /* Build combo array for JS */
        $js_combos = [];
        foreach ( $combos as $c ) {
            $js_combos[] = [ $c->attorney_name, $c->practice, $c->state ];
        }

        /* Build attorney profiles for JS (with extra fields) */
        $attorneys_raw = AFP_DB::get_attorneys( true );
        $attorney_meta = [];
        foreach ( $attorneys_raw as $a ) {
            $attorney_meta[ $a->name ] = [
                'title'    => $a->title,
                'bio'      => $a->bio,
                'email'    => $a->email,
                'phone'    => $a->phone,
                'linkedin' => $a->linkedin,
                'image'    => $a->image_url,
            ];
        }

        $office_names = array_column( (array) $offices, 'name' );
        $uid          = 'af_' . uniqid();

        ob_start();
        $this->render_styles( $s, $uid );
        $this->render_html( $s, $uid );
        $this->render_script( $s, $js_combos, $attorney_meta, $office_names, $uid );
        return ob_get_clean();
    }

    /* ── CSS ── */
    private function render_styles( $s, $uid ) {
        $p  = esc_attr( $s['primary_color'] );
        $a  = esc_attr( $s['accent_color'] );
        $bg = esc_attr( $s['background_color'] );
        $cb = esc_attr( $s['card_bg_color'] );
        $tx = esc_attr( $s['text_color'] );
        $r  = intval( $s['border_radius'] );
        $df = esc_attr( $s['display_font'] );
        $bf = esc_attr( $s['body_font'] );
        $min_col = intval($s['cards_per_row']) === 4 ? '240' : ( intval($s['cards_per_row']) === 2 ? '340' : '270' );
        echo "<style>
#$uid *{box-sizing:border-box;margin:0;padding:0}
#$uid{
  --p:{$p};--a:{$a};--p2:color-mix(in srgb,{$p} 80%,#fff);
  --bg:{$bg};--cb:{$cb};--tx:{$tx};
  --r:{$r}px;--rs:".max(4,$r-4)."px;
  --sh-sm:0 2px 8px rgba(0,0,0,.07);--sh-md:0 8px 32px rgba(0,0,0,.12);
  --g100:#f4f4f6;--g200:#e8e8ed;--g400:#9999aa;--g600:#555566;
  --ease:all .25s cubic-bezier(.4,0,.2,1);
  font-family:'{$bf}',sans-serif;color:var(--tx);background:var(--bg);
}
#$uid .af-hdr{background:var(--p);padding:44px 40px 36px;position:relative;overflow:hidden}
#$uid .af-hdr::before{content:'';position:absolute;top:-60px;right:-60px;width:300px;height:300px;
  border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.08) 0%,transparent 70%)}
#$uid .af-hdr h2{font-family:'{$df}',serif;font-size:2.3rem;font-weight:600;
  color:#fff;letter-spacing:-.02em;position:relative;line-height:1.2}
#$uid .af-hdr h2 span{color:var(--a)}
#$uid .af-hdr p{margin-top:8px;color:rgba(255,255,255,.5);font-size:.88rem;font-weight:300;position:relative}
#$uid .af-bar{background:var(--cb);border-bottom:1px solid var(--g200);
  padding:22px 40px;position:sticky;top:0;z-index:100;box-shadow:var(--sh-sm)}
#$uid .af-row{display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:12px;align-items:end;max-width:1200px;margin:0 auto}
#$uid .af-fg{display:flex;flex-direction:column;gap:5px}
#$uid .af-fg label{font-size:.68rem;font-weight:600;letter-spacing:.09em;text-transform:uppercase;color:var(--g400)}
#$uid .af-fg select,#$uid .af-fg input{height:44px;padding:0 36px 0 13px;
  border:1.5px solid var(--g200);border-radius:var(--rs);
  font-family:'{$bf}',sans-serif;font-size:.88rem;color:var(--tx);
  background:var(--cb);cursor:pointer;transition:var(--ease);
  appearance:none;-webkit-appearance:none;width:100%;
  background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239999aa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E\");
  background-repeat:no-repeat;background-position:right 11px center}
#$uid .af-fg input{background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='%239999aa' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E\")}
#$uid .af-fg select:focus,#$uid .af-fg input:focus{outline:none;border-color:var(--p2);box-shadow:0 0 0 3px color-mix(in srgb,var(--p) 15%,transparent)}
#$uid .af-fg select.af-on{border-color:var(--a);background-color:color-mix(in srgb,var(--a) 8%,white);color:var(--p);font-weight:500}
#$uid .af-btn-reset{height:44px;padding:0 18px;background:var(--p);color:#fff;
  border:none;border-radius:var(--rs);font-family:'{$bf}',sans-serif;
  font-size:.83rem;font-weight:500;cursor:pointer;transition:var(--ease);white-space:nowrap}
#$uid .af-btn-reset:hover{background:var(--p2);transform:translateY(-1px)}
#$uid .af-tags{max-width:1200px;margin:10px auto 0;display:flex;align-items:center;gap:8px;flex-wrap:wrap;min-height:22px}
#$uid .af-tags-lbl{font-size:.7rem;color:var(--g400);font-weight:500}
#$uid .af-tag{display:inline-flex;align-items:center;gap:5px;background:var(--p);
  color:#fff;padding:3px 9px 3px 11px;border-radius:100px;font-size:.73rem;font-weight:500;animation:af-pop .18s ease}
@keyframes af-pop{from{opacity:0;transform:scale(.8)}to{opacity:1;transform:scale(1)}}
#$uid .af-tag button{background:none;border:none;color:rgba(255,255,255,.55);cursor:pointer;font-size:.95rem;line-height:1;padding:0}
#$uid .af-tag.af-office-tag{background:var(--a);color:var(--p)}
#$uid .af-results{max-width:1200px;margin:0 auto;padding:28px 40px}
#$uid .af-meta{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}
#$uid .af-count{font-size:.83rem;color:var(--g600)}
#$uid .af-count strong{color:var(--p);font-weight:600}
#$uid .af-vtog{display:flex;gap:4px;background:var(--g100);padding:3px;border-radius:var(--rs)}
#$uid .af-vbtn{width:32px;height:32px;border:none;background:transparent;border-radius:5px;
  cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--g400);transition:var(--ease)}
#$uid .af-vbtn.af-active{background:var(--cb);color:var(--p);box-shadow:var(--sh-sm)}
#$uid .af-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax({$min_col}px,1fr));gap:18px}
#$uid .af-grid.af-list{grid-template-columns:1fr}
#$uid .af-card{background:var(--cb);border-radius:var(--r);overflow:hidden;
  box-shadow:var(--sh-sm);transition:var(--ease);animation:af-in .3s ease both;border:1px solid transparent}
@keyframes af-in{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
#$uid .af-card:hover{transform:translateY(-4px);box-shadow:var(--sh-md);border-color:var(--a)}
#$uid .af-avatar{width:100%;aspect-ratio:4/3;display:flex;align-items:center;
  justify-content:center;position:relative;overflow:hidden;background:linear-gradient(135deg,var(--p) 0%,var(--p2) 100%)}
#$uid .af-avatar img{width:100%;height:100%;object-fit:cover;object-position:top}
#$uid .af-avatar::after{content:'';position:absolute;bottom:0;left:0;right:0;height:45%;
  background:linear-gradient(transparent,rgba(0,0,0,.35))}
#$uid .af-initials{font-family:'{$df}',serif;font-size:2.8rem;font-weight:600;
  color:rgba(255,255,255,.92);letter-spacing:.05em;position:relative;z-index:1}
#$uid .af-pat{position:absolute;inset:0;
  background-image:radial-gradient(circle at 20% 80%,rgba(255,255,255,.1) 0%,transparent 50%),
                   radial-gradient(circle at 80% 20%,rgba(255,255,255,.07) 0%,transparent 50%)}
#$uid .af-ribbon{position:absolute;bottom:0;left:0;right:0;z-index:2;display:flex;gap:4px;padding:8px 10px;flex-wrap:wrap}
#$uid .af-opill{background:color-mix(in srgb,var(--a) 90%,black);color:var(--p);
  font-size:.6rem;font-weight:700;letter-spacing:.04em;padding:2px 7px;border-radius:100px;text-transform:uppercase}
#$uid .af-body{padding:18px}
#$uid .af-name{font-family:'{$df}',serif;font-size:1.2rem;font-weight:600;color:var(--p);margin-bottom:2px}
#$uid .af-role{font-size:.78rem;color:var(--g600);margin-bottom:10px}
#$uid .af-bio{font-size:.8rem;color:var(--g600);line-height:1.6;margin-bottom:12px;
  display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
#$uid .af-contact{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px}
#$uid .af-contact a{font-size:.75rem;color:var(--p2);text-decoration:none;display:flex;align-items:center;gap:4px}
#$uid .af-contact a:hover{color:var(--a)}
#$uid .af-match{font-size:.74rem;color:var(--a);font-weight:500;margin-bottom:10px;display:flex;align-items:center;gap:4px}
#$uid .af-pracs{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px}
#$uid .af-ptag{background:var(--g100);color:var(--g600);padding:2px 9px;border-radius:100px;font-size:.7rem;font-weight:500}
#$uid .af-ptag.af-hl{background:color-mix(in srgb,var(--a) 15%,white);color:color-mix(in srgb,var(--a) 70%,black);border:1px solid color-mix(in srgb,var(--a) 40%,white)}
#$uid .af-states{display:flex;align-items:center;gap:5px;font-size:.78rem;color:var(--g600)}
#$uid .af-sbadge{background:var(--p);color:#fff;font-size:.67rem;font-weight:600;padding:1px 7px;border-radius:100px}
#$uid .af-sbadge.af-hl{background:var(--a);color:var(--p)}
#$uid .af-grid.af-list .af-card{display:grid;grid-template-columns:100px 1fr}
#$uid .af-grid.af-list .af-avatar{aspect-ratio:unset;height:100%;min-height:90px}
#$uid .af-grid.af-list .af-initials{font-size:1.5rem}
#$uid .af-empty{grid-column:1/-1;text-align:center;padding:70px 20px;animation:af-in .3s ease}
#$uid .af-empty-icon{width:58px;height:58px;background:var(--g100);border-radius:50%;
  display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:var(--g400)}
#$uid .af-empty h3{font-family:'{$df}',serif;font-size:1.4rem;color:var(--p);margin-bottom:7px}
#$uid .af-empty p{color:var(--g400);font-size:.88rem}
@media(max-width:900px){
  #$uid .af-row{grid-template-columns:1fr 1fr}
  #$uid .af-hdr,#$uid .af-bar,#$uid .af-results{padding-left:20px;padding-right:20px}
}
@media(max-width:540px){
  #$uid .af-row{grid-template-columns:1fr}
  #$uid .af-hdr h2{font-size:1.8rem}
  #$uid .af-grid{grid-template-columns:1fr}
}
" . ( $s['custom_css'] ? "\n/* Custom CSS */\n" . strip_tags($s['custom_css']) : '' ) . "
</style>";
    }

    /* ── HTML ── */
    private function render_html( $s, $uid ) {
        $show = fn($key) => ! empty( $s[$key] );
        echo "<div id=\"" . esc_attr($uid) . "\" class=\"af-root\">";

        /* Header */
        if ( $show('show_header') ) {
            echo "<div class=\"af-hdr\"><h2>" . esc_html($s['header_title']) . " — <span>Our Attorneys</span></h2>"
               . "<p>" . esc_html($s['header_subtitle']) . "</p></div>";
        }

        /* Filter bar */
        echo "<div class=\"af-bar\"><div class=\"af-row\">";

        if ( $show('show_search') ) {
            echo "<div class=\"af-fg\"><label>Search by Name</label>"
               . "<input type=\"text\" id=\"{$uid}_name\" placeholder=\"e.g. Santosh\" oninput=\"afFilter('" . esc_js($uid) . "')\"></div>";
        }
        if ( $show('show_practice_filter') ) {
            echo "<div class=\"af-fg\"><label>Practice Area</label>"
               . "<select id=\"{$uid}_practice\" onchange=\"afFilter('" . esc_js($uid) . "')\"><option value=\"\">All Practice Areas</option></select></div>";
        }
        if ( $show('show_state_filter') ) {
            echo "<div class=\"af-fg\"><label>Bar Admission / State</label>"
               . "<select id=\"{$uid}_state\" onchange=\"afFilter('" . esc_js($uid) . "')\"><option value=\"\">All States</option></select></div>";
        }
        if ( $show('show_office_filter') ) {
            echo "<div class=\"af-fg\"><label>Office Location</label>"
               . "<select id=\"{$uid}_office\" onchange=\"afFilter('" . esc_js($uid) . "')\"><option value=\"\">All Offices</option></select></div>";
        }

        echo "<button class=\"af-btn-reset\" onclick=\"afReset('" . esc_js($uid) . "')\">Reset</button>";
        echo "</div><div class=\"af-tags\" id=\"{$uid}_tags\"></div></div>";

        /* Results */
        echo "<div class=\"af-results\">";
        echo "<div class=\"af-meta\">"
           . "<div class=\"af-count\" id=\"{$uid}_count\">Loading…</div>";

        if ( $show('show_view_toggle') ) {
            echo "<div class=\"af-vtog\">"
               . "<button class=\"af-vbtn af-active\" id=\"{$uid}_gbtn\" onclick=\"afView('" . esc_js($uid) . "','grid')\" title=\"Grid\">"
               . "<svg width=\"15\" height=\"15\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\"><rect x=\"3\" y=\"3\" width=\"7\" height=\"7\"/><rect x=\"14\" y=\"3\" width=\"7\" height=\"7\"/><rect x=\"3\" y=\"14\" width=\"7\" height=\"7\"/><rect x=\"14\" y=\"14\" width=\"7\" height=\"7\"/></svg>"
               . "</button>"
               . "<button class=\"af-vbtn\" id=\"{$uid}_lbtn\" onclick=\"afView('" . esc_js($uid) . "','list')\" title=\"List\">"
               . "<svg width=\"15\" height=\"15\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\"><line x1=\"3\" y1=\"6\" x2=\"21\" y2=\"6\"/><line x1=\"3\" y1=\"12\" x2=\"21\" y2=\"12\"/><line x1=\"3\" y1=\"18\" x2=\"21\" y2=\"18\"/></svg>"
               . "</button></div>";
        }

        echo "</div><div class=\"af-grid\" id=\"{$uid}_grid\"></div></div></div>";
    }

    /* ── JavaScript ── */
    private function render_script( $s, $combos, $meta, $offices, $uid ) {
        $cj  = wp_json_encode( $combos );
        $mj  = wp_json_encode( $meta );
        $oj  = wp_json_encode( $offices );
        $sj  = wp_json_encode([
            'show_bio'          => ! empty($s['show_bio']),
            'show_email'        => ! empty($s['show_email']),
            'show_phone'        => ! empty($s['show_phone']),
            'show_linkedin'     => ! empty($s['show_linkedin']),
            'show_practices'    => ! empty($s['show_practices']),
            'show_states'       => ! empty($s['show_states']),
            'show_office_pills' => ! empty($s['show_office_pills']),
            'card_style'        => $s['card_style'],
        ]);
        echo "<script>
(function(){
var UID=".wp_json_encode($uid).";
var COMBOS={$cj};
var META={$mj};
var OFFICES={$oj};
var CFG={$sj};

var PROFILES=(function(){
  var map={};
  COMBOS.forEach(function(r){
    var n=r[0],p=r[1],st=r[2];
    if(!map[n]) map[n]={name:n,practices:[],states:[],combos:[]};
    if(map[n].practices.indexOf(p)<0) map[n].practices.push(p);
    if(map[n].states.indexOf(st)<0)   map[n].states.push(st);
    map[n].combos.push([p,st]);
  });
  return Object.values(map).sort(function(a,b){return a.name.localeCompare(b.name);});
})();

function \$e(s){return document.getElementById(UID+'_'+s);}
function escH(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\"/g,'&quot;');}

var GRADS=[['#0d2340','#1a3a5c'],['#1a3a5c','#2c5282'],['#0d3b5e','#1a5276'],['#1b3a4b','#1f5a7a'],['#0a2744','#154360'],['#162032','#1c3f6e']];
function getGrad(n){return GRADS[n.charCodeAt(0)%GRADS.length];}
function getInitials(n){return n.split(' ').map(function(w){return w[0];}).join('').slice(0,2).toUpperCase();}

function init(){
  var practices=[],states=[];
  COMBOS.forEach(function(r){
    if(practices.indexOf(r[1])<0) practices.push(r[1]);
    if(states.indexOf(r[2])<0)    states.push(r[2]);
  });
  practices.sort();states.sort();
  var pS=\$e('practice'),sS=\$e('state'),oS=\$e('office');
  if(pS) practices.forEach(function(p){pS.add(new Option(p,p));});
  if(sS) states.forEach(function(s){sS.add(new Option(s,s));});
  if(oS) OFFICES.forEach(function(o){oS.add(new Option(o,o));});
}

function getF(){
  var nm=\$e('name'),pr=\$e('practice'),st=\$e('state'),of=\$e('office');
  return{name:nm?nm.value.trim().toLowerCase():'',practice:pr?pr.value:'',state:st?st.value:'',office:of?of.value:''};
}

window.afFilter=function(uid){
  if(uid!==UID)return;
  var f=getF();
  ['practice','state','office'].forEach(function(k){
    var el=\$e(k); if(el) el.classList.toggle('af-on',el.value!=='');
  });
  var results=PROFILES.filter(function(att){
    if(f.name&&att.name.toLowerCase().indexOf(f.name)<0)return false;
    if(f.practice&&f.state) return att.combos.some(function(c){return c[0]===f.practice&&c[1]===f.state;});
    if(f.practice&&att.practices.indexOf(f.practice)<0)return false;
    if(f.state&&att.states.indexOf(f.state)<0)return false;
    return true;
  });
  renderCards(results,f);
  renderTags(f);
  \$e('count').innerHTML='Showing <strong>'+results.length+'</strong> attorney'+(results.length!==1?'s':'');
};

function renderCards(atts,f){
  var grid=\$e('grid'); grid.innerHTML='';
  if(!atts.length){
    grid.innerHTML='<div class=\"af-empty\"><div class=\"af-empty-icon\"><svg width=\"26\" height=\"26\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.5\"><circle cx=\"11\" cy=\"11\" r=\"8\"/><path d=\"m21 21-4.35-4.35\"/></svg></div><h3>No attorneys found</h3><p>Try adjusting your search or filters</p></div>';
    return;
  }
  atts.forEach(function(att,i){
    var m=META[att.name]||{};
    var g=getGrad(att.name);
    var pr=att.practices.slice().sort();
    var st=att.states.slice().sort();
    var stLabel=st.length>3?st.slice(0,3).join(', ')+' +'+(st.length-3)+' more':st.join(', ');

    var avatarHTML='';
    if(CFG.card_style!=='minimal'){
      var inner='';
      if(m.image){
        inner='<img src=\"'+escH(m.image)+'\" alt=\"'+escH(att.name)+'\" loading=\"lazy\">';
      } else {
        inner='<div class=\"af-pat\"></div><div class=\"af-initials\">'+getInitials(att.name)+'</div>';
      }
      var officesToShow=f.office?[f.office]:OFFICES;
      var ribbonHTML=CFG.show_office_pills?officesToShow.map(function(o){return'<span class=\"af-opill\">'+escH(o)+'</span>';}).join(''):'';
      avatarHTML='<div class=\"af-avatar\" style=\"background:linear-gradient(135deg,'+g[0]+' 0%,'+g[1]+' 100%)\">'+inner+'<div class=\"af-ribbon\">'+ribbonHTML+'</div></div>';
    }

    var pracHTML=CFG.show_practices?pr.map(function(p){return'<span class=\"af-ptag'+(f.practice===p?' af-hl':'')+'\">'+escH(p)+'</span>';}).join(''):'';
    var matchHTML='';
    if(f.practice&&f.state&&att.combos.some(function(c){return c[0]===f.practice&&c[1]===f.state;})){
      matchHTML='<div class=\"af-match\">&#10003; Practices '+escH(f.practice)+' in '+escH(f.state)+'</div>';
    } else if(f.practice&&att.practices.indexOf(f.practice)>=0){
      matchHTML='<div class=\"af-match\">&#10003; '+att.states.length+' state'+(att.states.length>1?'s':'')+' for '+escH(f.practice)+'</div>';
    } else if(f.office){
      matchHTML='<div class=\"af-match\">&#10003; Available at '+escH(f.office)+'</div>';
    }

    var stateHL=f.state&&att.states.indexOf(f.state)>=0;
    var bioHTML=CFG.show_bio&&m.bio?'<div class=\"af-bio\">'+escH(m.bio)+'</div>':'';
    var contactHTML='';
    if(CFG.show_email&&m.email) contactHTML+='<a href=\"mailto:'+escH(m.email)+'\">&#9993; '+escH(m.email)+'</a>';
    if(CFG.show_phone&&m.phone) contactHTML+='<a href=\"tel:'+escH(m.phone)+'\">&#128222; '+escH(m.phone)+'</a>';
    if(CFG.show_linkedin&&m.linkedin) contactHTML+='<a href=\"'+escH(m.linkedin)+'\" target=\"_blank\" rel=\"noopener\">&#128279; LinkedIn</a>';
    if(contactHTML) contactHTML='<div class=\"af-contact\">'+contactHTML+'</div>';

    var statesHTML=CFG.show_states?'<div class=\"af-states\"><svg width=\"12\" height=\"12\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\"><path d=\"M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z\"/><circle cx=\"12\" cy=\"10\" r=\"3\"/></svg><span>'+escH(stLabel)+'</span><span class=\"af-sbadge'+(stateHL?' af-hl':'')+'\">'+att.states.length+'</span></div>':'';

    var card=document.createElement('div');
    card.className='af-card';
    card.style.animationDelay=(i*35)+'ms';
    card.innerHTML=avatarHTML+'<div class=\"af-body\"><div class=\"af-name\">'+escH(att.name)+'</div>'+(m.title?'<div class=\"af-role\">'+escH(m.title)+'</div>':'')+matchHTML+bioHTML+contactHTML+(CFG.show_practices?'<div class=\"af-pracs\">'+pracHTML+'</div>':'')+statesHTML+'</div>';
    grid.appendChild(card);
  });
}

function renderTags(f){
  var c=\$e('tags'); c.innerHTML='';
  var items=[
    f.name&&{label:'Name: \"'+f.name+'\"',key:'name',office:false},
    f.practice&&{label:f.practice,key:'practice',office:false},
    f.state&&{label:f.state,key:'state',office:false},
    f.office&&{label:'Office: '+f.office,key:'office',office:true},
  ].filter(Boolean);
  if(!items.length)return;
  var lbl=document.createElement('span');lbl.className='af-tags-lbl';lbl.textContent='Active filters:';c.appendChild(lbl);
  items.forEach(function(item){
    var tag=document.createElement('span');
    tag.className='af-tag'+(item.office?' af-office-tag':'');
    tag.innerHTML=escH(item.label)+'<button onclick=\"afClearFilter(\\''+UID+'\\',\\''+item.key+'\\')\" aria-label=\"Remove\">&#215;</button>';
    c.appendChild(tag);
  });
}

window.afClearFilter=function(uid,key){
  if(uid!==UID)return;
  if(key==='name')\$e('name').value='';
  else{\$e(key).value='';\$e(key).classList.remove('af-on');}
  afFilter(UID);
};
window.afReset=function(uid){
  if(uid!==UID)return;
  var n=\$e('name'); if(n) n.value='';
  ['practice','state','office'].forEach(function(k){var el=\$e(k);if(el){el.value='';el.classList.remove('af-on');}});
  afFilter(UID);
};
window.afView=function(uid,v){
  if(uid!==UID)return;
  \$e('grid').classList.toggle('af-list',v==='list');
  \$e('gbtn').classList.toggle('af-active',v==='grid');
  \$e('lbtn').classList.toggle('af-active',v==='list');
};

init(); afFilter(UID);
})();
</script>";
    }
}
