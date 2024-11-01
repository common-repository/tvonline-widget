<?php
  /**
   *  Plugin Name: Tv Online
   *  Version: 1.1
   *  Plugin URI: http://www.digitvonline.ro
   *  Description: Acest plugin va permite sa alegeti posturile tv favorite si sa le afisati sub forma unui widget, pe blogurile personale.
   *  Author: GeorgeJipa
   *  Author URI: http://www.digitvonline.ro 
   **/

  /**
   *  Include feed.php, necesar functiei fetch_feed();
   **/     
  include_once(ABSPATH . WPINC . '/feed.php');   
  
  /**
   *  Constante necesare functionarii plugin-ului
   **/
   define('TV_API_PROVIDER', 'http://digitvonline.ro/');
   define('TV_API_URL', TV_API_PROVIDER.'api/index.php');
         
  /**
   *  getCategFromAPI(): preia din API toate categoriile de posturi tv si le confrunta cu categoriile bifate anterior (daca au fost bifate) 
   **/
   function getCategFromAPI(){
    
    $data = array('method' => 'getCateg', 'time' => time());
    $query = http_build_query($data);
    
    $rss = fetch_feed(TV_API_URL.'?'.$query); 
    $items = $rss->get_items();
    
    foreach($items as $item){
      $categ = $item->data['data'];
      $idcateg = $item->data['attribs']['']['id'];
      
      if(get_option('tw_categ_bifate') == TRUE){
        $categBifate = unserialize(get_option('tw_categ_bifate'));
        $checked = (in_array($idcateg, $categBifate)) ? 'checked' : '';
      } else {
        $checked = '';
      }
      echo '<input type="checkbox" name="categorii[]" value="'.$idcateg.'" '.$checked.'/> '.$categ.' ';
    }
   }

  /**
   *  getTvsCateg(): preia din API toate categoriile de posturile Tv si le confrunta cu categoriile bifate anterior (daca au fost bifate) 
   **/
   function getTvsCateg(){
    
    $categBifate = unserialize(get_option('tw_categ_bifate')); 
    $data = array('method' => 'getTvsCateg', 'ids' => $categBifate, 'time' => time());
    $query = http_build_query($data);
    
    $rss = fetch_feed(TV_API_URL.'?'.$query);
    $items = $rss->get_items();
    
    foreach($items as $item){
      $post = $item->data['data'];
      $idpost = $item->data['attribs']['']['idpost'];
      $inaltime = $item->data['attribs']['']['inaltime']+70;
      $latime = $item->data['attribs']['']['latime']+20;

      if(get_option('tw_post_bifate') == TRUE) {
        $postBifate = unserialize(get_option('tw_post_bifate'));
        $checked = (in_array($idpost, $postBifate)) ? 'checked' : '';
      } else  {
        $checked = '';
      }
      echo '<input type="checkbox" name="posturi[]" value="'.$idpost.'" '.$checked.'/> <a href="'.TV_API_PROVIDER.'api/tv.php?id='.$idpost.'" onclick="javascript: window.open(\''.TV_API_PROVIDER.'api/tv.php?id='.$idpost.'\', \'\', \'width='.$latime.', height='.$inaltime.'\'); return false;" target="_blank">'.$post.'</a> ';
    }
   }
  
  /**
   *  getTvs(): preia informatii din API despre toate posturile Tv bifate anterior, in partea de administrare
   **/
   function getTvs(){ // este setat automat la maxim 5 posturi tv per categorie
    
    // Afisam widget doar daca exista posturi radio bifate
    if(get_option('tw_post_bifate') == TRUE){
      $max = get_option('tw_widget_max');
            
      $postBifate = unserialize(get_option('tw_post_bifate'));
      $data = array('method' => 'getTvs', 'ids' => $postBifate);
      $query = http_build_query($data);
      $rss = fetch_feed(TV_API_URL.'?'.$query);
      $items = $rss->get_items();

      echo '<ul>';
      foreach($items as $item){
        $idcat = $item->data['attribs']['']['idcat'];
        $numecat = $item->data['attribs']['']['numecat'];
        $titlu = $item->data['attribs']['']['titlu'];
      
        echo '<li><strong style="font-size: 14px;">'.$numecat.'</strong>';
        $i=1;
		echo '<ul>';
        foreach($item->data['child']['']['post'] as $post){
          $numepost = $post['data'];
          $idpost = $post['attribs']['']['idpost'];
          $inaltime = $post['attribs']['']['inaltime']+70;
          $latime = $post['attribs']['']['latime']+20;
		         
          if($i<=$max) echo '<li><a href="'.TV_API_PROVIDER.'api/tv.php?id='.$idpost.'" onclick="javascript: window.open(\''.TV_API_PROVIDER.'api/tv.php?id='.$idpost.'\', \'\', \'width='.$latime.', height='.$inaltime.'\'); return false;" target="_blank" rel="nofollow" title="TvOnline '.$numepost.'">'.$numepost.'</a></li>';
          $i++;
        }
        echo '</ul></li>';
      }
      echo '<a  href="'.TV_API_PROVIDER.'" title="Tv Online" target="_blank"><img src="'.plugins_url('/images/tvonline.gif', __FILE__).'" style="border: none;" alt="Tv Online" /></a>';
      echo '</ul>'; 
    } else {
      echo '<ul><li>Niciun post Tv selectat!</li></ul>';
    }
   }
   
  /**
   *  tw_widget(): inregistrare widget
   **/     
  function register_tw_widget($args) {
    extract($args);
    
    echo $before_widget;
    $title = get_option('tw_widget_title');
    echo $args['before_title'].' '.$title.' '.$args['after_title'];
    getTvs(); 
    echo $after_widget;
  }
  	  
  function register_tw_control(){
    $max = get_option('tw_widget_max');
    $title = get_option('tw_widget_title');
    
    echo '<p><label>Titlu TvWidget: <input name="title" type="text" value="'.$title.'" /></label></p>';
    echo '<p><label>Posturi Tv / categorie: <input name="max" type="text" value="'.$max.'" /></label></p>';
      
    if(isset($_POST['max'])){
      update_option('tw_widget_max', attribute_escape($_POST['max']));
      update_option('tw_widget_title', attribute_escape($_POST['title']));
    }
  }    
  
  function tw_widget() {
  	 register_widget_control('TvWidget', 'register_tw_control'); 
  	 register_sidebar_widget('TvWidget', 'register_tw_widget');
  }          
   
  /**
   *  tw_admin(): partea de administrare a plugin-ului
   **/     
   function tw_admin(){
    echo '<div class="wrap">';
    echo '<h2>Setari TvWidget</h2>';
    if(isset($_POST['scategorii']) && isset($_POST['categorii'])){ // in cazul in care una dintre ele nu este exista, nu se face submit
        $categorii = serialize($_POST['categorii']);
        if(get_option('tw_categ_bifate') === FALSE){
          add_option('tw_categ_bifate', $categorii);
        } else {
          delete_option('tw_categ_bifate');
          add_option('tw_categ_bifate', $categorii);
        }
    }
    echo '<div class="widefat" style="padding: 5px">1) Alege una sau mai multe categorii:<br /><br />';
    echo '<form method="post" name="categorii" target="_self">';
    getCategFromAPI();
    echo '<input name="scategorii" type="hidden" value="yes" />';
    echo '<br /><br /><input type="submit" name="Submit" value="Listeaza posturi Tv &raquo;" />';    
    echo '</form>';
    echo '</div>';
    echo '<br />';
    if(isset($_POST['scategorii']) && isset($_POST['categorii'])){
      echo '<div class="widefat fade" style="padding: 5px">2) Alege posturile Tv pe care vrei sa le afisezi <br /><br />';
      echo '<form method="post" name="posturi" target="_self">';      
      getTvsCateg();
      echo '<input name="sposturi" type="hidden" value="yes" />';
      echo '<br /><br /><input type="submit" name="Submitt" value="Salveaza posturi Tv &raquo;" />';    
      echo '</form>';
      echo '</div>';   
    }
    if(isset($_POST['sposturi']) && isset($_POST['posturi'])){
        $posturi = serialize($_POST['posturi']);
        if(get_option('tw_post_bifate') === FALSE){
          add_option('tw_post_bifate', $posturi);
        } else {
          delete_option('tw_post_bifate');
          add_option('tw_post_bifate', $posturi);
        }
        echo '<div id="message" class="updated fade"><p><strong>Posturile Tv au fost salvate in baza de date!</strong></p></div>';        
      }    
    echo '</div>';
   }

  /**
   *  tw_addpage(): adauga pagina de administrare in meniul Wordpress
   **/     
  function tw_addpage() {
    add_menu_page('TV Widget', 'TV Widget', 10, __FILE__, 'tw_admin');
  }
  
  /**
   *  tw_install() & tw_uninstall() - functii care se autoinitializeaza la activare si dezactivare plugin
   **/
  function tw_install(){
    add_option('tw_widget_max', '5');
    add_option('tw_widget_title', 'Tv Online');
  }
  
  function tw_uninstall(){
    delete_option('tw_widget_max');
    delete_option('tw_widget_title');
    delete_option('tw_post_bifate');
    delete_option('tw_categ_bifate');
  }     
  
  /**
   *  Actions & Hooks
   **/     
  add_action('admin_menu', 'tw_addpage');
  add_action("plugins_loaded", 'tw_widget');
  register_activation_hook(__FILE__, 'tw_install');
  register_deactivation_hook(__FILE__, 'tw_uninstall');   
?>
