<?php

new RIBBONS;
var_dump(@$_SESSION['ribbons']['post']);
return RIBBONS::$output;

class RIBBONS{
    static
    $db_file = './_HOLDING/sqlite/webapp.sqlite3'
    ,$ribbons_table = 'ribbons'
    ,$bad_db = '<p class="error message">Database failure.  Please try again.</p>'
    ,$output = null
    ,$dbcnnx = null
    ,$asteroids = null
    ,$planets = null
    ,$devices = null
    ,$effects = null
    ;
    
    function __construct(){
        @session_start();
        if( empty($_SESSION['crispy']) ){
            static::$output .= '<p class="error message">Bad session.</p>';
            return;
        }
        
        if( ! $this->init_database() ){
            return;
        }
        
        // ALL SOI bodies are called "planets" here to avoid confusion with IT terms.
        static::$planets = array(
'Kerbol'=>  '001000','Moho'=>    '100000','Eve'=>   '111010','Gilly'=> '101001','Kerbin'=> '111110',
'Mun'=>     '100100','Minmus'=>  '101101','Duna'=>  '111100','Ike'=>   '101101','Dres'=>   '101000',
'Jool'=>    '011000','Laythe'=>  '110000','Vall'=>  '100000','Tylo'=>  '100110','Bop'=>    '100101',
'Pol'=>     '100001','Eeloo'=>   '101000',
'Asteroid'=>'101001'
        );
        $attributes = array('surf','atmo','sync','anom','chal','eeva');
        foreach( static::$planets as $planet => $attribs ){
            static::$planets[$planet] = array();
            foreach( $attributes as $key => $val ){
                static::$planets[$planet][$val] = $attribs[$key]; // Strings are ~ like arrays.
            }
        }
        static::$asteroids = array(
            'Asteroid - Duna','Asteroid - Eve','Asteroid - Jool','Asteroid - Kerbin','Asteroid - Kerbol','Asteroid - Moho'
        );
        // Devices in order of display: Priority, Name, type, description.
        static::$devices = array(
            // Name                     type_priority   Description.
            'Orbit'             => array('mans:8',      'Periapsis above the atmosphere and apoapsis within the sphere of influence.')
            ,'Equatorial'       => array('mans:5',      'Inclination less than 5 degrees and, apoapsis and periapsis within 5% of each other.')
            ,'Polar'            => array('mans:4',      'Polar orbit capable of surveying the entire surface.')
            ,'Rendezvous'       => array('mans:6',      'Docked two craft in orbit, or maneuvered two orbiting craft within 100m for a sustained time.')
            ,'Land Nav'         => array('mans:surf:9', 'Ground travel at least 30km or 1/5ths a world\'s circumference (whichever is shorter).')
            ,'Atmosphere'       => array('mans:atmo:3', 'Controlled maneuvers using wings or similar. Granted only if craft can land and then take off, or perform maneuvers and then attain orbit.')
            ,'Geosynchronous'   => array('mans:spec:7', 'Achieve geosynchronous orbit around the world; or drag the body into geosynchronous orbit around another; or construct a structured, line-of-sight satellite network covering a specific location.')
            ,'Kerbol Escape'    => array('mans:spec:10','Achieved solar escape velocity - for Kerbol only.')
            ,'Probe'            => array('crafts',      'Autonomous craft which does not land.')
            ,'Capsule'	        => array('crafts',      'Manned craft which does not land, or only performs a single, uncontrolled landing.')
            ,'Resource'      	=> array('crafts',      'Installation on the surface or in orbit, capable of mining and/or processing resources.')
            ,'Aircraft'     	=> array('crafts',      'Winged craft capable of atmospheric flight, with or without any atmosphere - does not grant Flight Wings device.')
            ,'Multi-Part Ship'	=> array('crafts',      'Orbital vessel capable of docking and long-term habitation by multiple Kerbals.')
            ,'Station'      	=> array('crafts',      'A craft constructed from multiple parts in orbit.')
            ,'Armada'	        => array('crafts',      'Three or more vessels, staged in orbit for a trip to another world and launched within one week during one encounter window.')
            ,'Armada 2'     	=> array('crafts',      'Three or more vessels, staged in orbit for a trip to another world and launched within one week during one encounter window.')
            ,'Impactor'	        => array('crafts:surf', 'Craft was destroyed by atmospheric or surface friction.')
            ,'Probe Lander' 	=> array('crafts:surf', 'Autonomous craft which landed on a world\'s surface.')
            ,'Probe Rover'	    => array('crafts:surf', 'Autonomous craft which landed and performed controlled surface travel.')
            ,'Flag or Monument'	=> array('crafts:surf', 'A marker left on the world.')
            ,'Lander'       	=> array('crafts:surf', 'A craft carrying one or more Kerbals which landed without damage.')
            ,'Rover'	        => array('crafts:surf', 'A vehicle which landed and then carried one or more Kerbals across the surface of the world.')
            ,'Base'	            => array('crafts:surf', 'A permanent ground construction capable of long-term habitation by multiple Kerbals')
            ,'Base 2'	        => array('crafts:surf', 'A permanent ground construction capable of long-term habitation by multiple Kerbals')
            ,'Meteor'	        => array('crafts:atmo', 'Craft was destroyed due to atmospheric entry.')
            ,'Extreme EVA'  	=> array('crafts:spec', 'Landed and returned to orbit without the aid of a spacecraft.')
            ,'Kerbal Lost'      => array('misc:0',      'A Kerbal was killed or lost beyond the possibility of rescue.')
            ,'Kerbal Saved'     => array('misc:1',      'Returned a previously stranded Kerbal safely to Kerbin.')
            ,'Return Chevron'   => array('misc:12',     'Returned any craft safely to Kerbin from the world.')
            ,'Anomaly'          => array('misc:spec:2', 'Discovered and closely inspected a genuine Anomaly.')
            ,'Challenge Wreath' => array('misc:spec:11','')
        );
        static::$effects = array( // EffectName => array(type[,name])
            'None' => array('radio','ribbon_effect'),
            'Ribbon' => array('radio','ribbon_effect'),
            'High Contrast Ribbons' => array('radio','ribbon_effect'),
            'Lightened HC' => array('radio','ribbon_effect'),
            'Dense HC' => array('radio','ribbon_effect'),
            'Lightened Dense HC' => array('radio','ribbon_effect'),
            'Darken Bevel' => array('checkbox'),
            'Lighten Bevel' => array('checkbox')
        );
        
        $this->strip_post();
        if( empty($_SESSION['ribbons']['post']) ){
            $this->load_ribbons();
        }
        
        static::$output .= $this->get_form();
        
    }
    
    private function init_database(){
        if(
            ! is_writable(static::$db_file)
            OR ! is_writable(dirname(static::$db_file))
        ){
            static::$output .= get_called_class().': Can\'t find or write to DB file.';
            return false;
        }
        try{
            static::$dbcnnx = new PDO('sqlite:'.static::$db_file);
        }
        catch( PDOException $Exception ){
            static::$output .= 'Connect: '.static::$bad_db;
            return false;
        }
        if(
            $stmt = static::$dbcnnx->prepare("
SELECT name FROM sqlite_master
WHERE type='table' AND name='".static::$ribbons_table."';
")
            AND $stmt->execute()
            AND $result = $stmt->fetch(PDO::FETCH_ASSOC)
        ){
            $table_exists = true;
        }else{
            $stmt_string = "
CREATE TABLE ".static::$ribbons_table."(
id INTEGER NOT NULL UNIQUE
,data TEXT NOT NULL
);
";
            if(
                $stmt = static::$dbcnnx->prepare($stmt_string)
                AND $stmt->execute()
            ){
                $table_created = true;
                static::$output .= '<p class="warn message">Ribbons table created.</p>';
            }else{
                static::$output .= '<p class="error message">Ribbons table creation:</p> '.static::$bad_db;
                return false;
            }
        }
        return true;
    }

    private function strip_post(){
        if( empty($_POST['ribbons_task']) ){ return; }
        if( $_POST['ribbons_task'] === 'generate' ){
            $this->generate_ribbons_image();
            return;
        }
        $post = $_POST;
        $planets_visited = array();
        foreach( $post as $key => $val ){
            if(
                $key === 'ribbons_task'
                OR $key === 'ribbons_submit'
                OR strlen($key) > 40
                OR strlen($val) > 40
                OR $val === 'None'
                OR $val == '0'
            ){
                unset($post[$key]);
                continue;
            }
            if( $val === 'None' ){ continue; }
            $planet = preg_replace('/_.*/i','',$key);
            if( $planet === 'Asteroid' ){
                $planet = preg_replace('/(Asteroid_-_[^_]+)_.*/i','$1',$key);
            }
            if( array_key_exists(preg_replace('/_/i',' ',$planet),static::$planets) ){
                $planets_visited[$planet] = $planet;
            }
        }
        foreach($planets_visited as $planet){
            $post[$planet.':visited'] = 'on';
        }
        $_SESSION['ribbons']['post'] = $post;
        
        $this->save_ribbons();
        
        session_write_close();
        header('Location: http://'.$_SERVER['HTTP_HOST'].preg_replace('/\?.*/i','',$_SERVER['REQUEST_URI']).'?page='.@$_GET['page'] );
        exit('<p><a title="Click to continue." href="http://'.$_SERVER['HTTP_HOST'].preg_replace('/\?.*/i','',$_SERVER['REQUEST_URI']).'?page='.@$_GET['page'].'">Click here to continue.</a></p>');
    }
    
    private function generate_ribbons_image(){
//        if( empty($_SESSION['ribbons']['post']) ){ return; }
        $post = $_SESSION['ribbons']['post'];
        $ribbons = array();
        foreach( $post as $prop => $val ){
            $prop = preg_replace('/_+/',' ',$prop);
            $patt = '/^([^:]*)(:([^:]*))?$/i';
            $ribbon = preg_filter($patt,'$1',$prop);
            $device = preg_filter($patt,'$3',$prop);
            if(
                ! $device
                OR $device === 'visited'
                OR $ribbon === 'ribbon effect'
            ){ continue; }
            if(
                $ribbon === 'Grand Tour'
                AND $device === 'Orbit'
                OR $device === 'Landing'
            ){
                $i=1;while($i<=$val){
                    $image_name = $device.' ';
                    if( $i <= 8 ){
                        $image_name .= $i;
                    }else{
                        $image_name .= ($i-8).' Silver';
                    }
                    $ribbons[$ribbon][] = $image_name.'.png';
                $i++;}
            }else{
                $ribbons[$ribbon][] = $device.'.png';
            }
        }
        
        foreach($ribbons as $ribbon => $images){
            $image_path = './KSP_images/ribbons/';
            if( $ribbon === 'Grand Tour' ){
                $image_path .= 'shield/';
            }
            if( $ribbon === 'Grand Tour' ){
                $ribbon_image = 'Base Colours.png';
            }else{
                $ribbon_image = $ribbon.'.png';
            }
            $ribbon_image = imagecreatefrompng($image_path.$ribbon_image);
            foreach($images as $image){
                $image = imagecreatefrompng($image_path.$image);
                imagecopy($ribbon_image, $image, 0,0, 0,0, 120,97);
                imagedestroy($image);
            }
        }

        header('Content-Type: image/png');
        imagepng($ribbon_image);
        imagedestroy($ribbon_image);
        
        
//var_dump($ribbons);
die();
    }
    
    private function save_ribbons(){
        if(
            empty($_SESSION['logged_in'])
            OR empty($_SESSION['user']['id'])
            OR empty($_SESSION['ribbons']['post'])
        ){ return; }
        $data = '';
        foreach($_SESSION['ribbons']['post'] as $key => $val){
            if(!empty($data)){$data .= '|';}
            $data .= $key.'='.$val;
        }
        if(
            $stmt = static::$dbcnnx->prepare("
SELECT data FROM ".static::$ribbons_table."
WHERE id=:id;
")
            AND $stmt->bindValue(':id', $_SESSION['user']['id'], PDO::PARAM_INT)
            AND $stmt->execute()
            AND $result = $stmt->fetch(PDO::FETCH_ASSOC)
        ){
            if(
                $stmt = static::$dbcnnx->prepare("
UPDATE ".static::$ribbons_table." SET data=:data
WHERE id=:id;
")
                AND $stmt->bindValue(':data', $data, PDO::PARAM_STR)
                AND $stmt->bindValue(':id', $_SESSION['user']['id'], PDO::PARAM_INT)
                AND $stmt->execute()
            ){
                $data_saved = true;
            }else{
                static::$output .= '<p class="error message">Ribbons table update:</p> '.static::$bad_db;
                return false;
            }
        }else{
            if(
                $stmt = static::$dbcnnx->prepare("
INSERT INTO ".static::$ribbons_table." (id,data)
VALUES (:id,:data);
")
                AND $stmt->bindValue(':id', $_SESSION['user']['id'], PDO::PARAM_INT)
                AND $stmt->bindValue(':data', $data, PDO::PARAM_STR)
                AND $stmt->execute()
            ){
                $data_saved = true;
            }else{
                static::$output .= '<p class="error message">Ribbons table insert:</p> '.static::$bad_db;
                return false;
            }
        }
        if( ! empty($data_saved) ){
            static::$output .= '<p class="success message">Your ribbons were saved.</p>';
            return true;
        }
    }
    
    private function load_ribbons(){
        if( empty($_SESSION['logged_in']) ){ return; }
        if(
            ! empty($_SESSION['user']['id'])
            AND $stmt = static::$dbcnnx->prepare("
SELECT * FROM ".static::$ribbons_table."
WHERE id=:id;
")
            AND $stmt->bindValue(':id', $_SESSION['user']['id'], PDO::PARAM_INT)
            AND $stmt->execute()
            AND $result = $stmt->fetch(PDO::FETCH_ASSOC)
        ){
            $data_loaded = explode('|',$result['data']);
            $data = array();
            $patt = '^([^=]*)=(.*)$';
            foreach($data_loaded as $val){
                $prop = preg_replace('/'.$patt.'/i','$1',$val);
                $val = preg_replace('/'.$patt.'/i','$2',$val);
                $data[$prop] = $val;
            }
            $_SESSION['ribbons']['post'] = $data;
            static::$output .= '<p class="success message">Your saved ribbons have been loaded.</p>';
            return true;
        }else{
            static::$output .= '<p class="warn message">No saved ribbons were found.</p>';
            return false;
        }
    }
    
    private function get_form(){
        
        function add_device($type, $name, $device, $desc='', $class=''){
            $name = preg_replace('/\s+/i','_',$name);
            $value = preg_replace('/\s+/i','_',$device);
            $id = uniqid($device.'_',false);
            $add = '';
            if(
                (
                    ! empty($_SESSION['ribbons']['post'][$name])
                    AND $_SESSION['ribbons']['post'][$name] === $value
                )
                OR $device === 'None' // Default
            ){
                $add .= ' checked="checked"';
            }
            
            $return = '
        <div class="device input_box '.$value.' '.$class.'" title="'.$desc.'">
            <label';
            if( $device !== 'None' AND $device !== 'Visited' ){
                $return .= ' style="background-image:url(\'./KSP_images/ribbons/icons/'.$device.'.png\');"';
            }
            
            $return .= ' for="'.$id.'">'.$device.'</label>
            <input type="'.$type.'" id="'.$id.'" name="'.$name.'" value="'.$value.'"'.$add.'/>
        </div>';
            return $return;
        }
        
        // Setup devices...
        $types = array('mans','mans:surf','mans:atmo','mans:spec','crafts','crafts:surf','crafts:atmo','crafts:spec','misc','misc:spec');
        foreach( $types as $type ){
            static::$devices[$type] = array();
        }
        foreach( static::$devices as $key => $val ){
            if( in_array($key,$types) ){ continue; }
            $type = preg_replace('/:\d*$/i','',$val[0]);
            static::$devices[$type][$key] = $val[1];
        }
        
        $form = '';
        if( ! empty($_SESSION['ribbons']['post']) ){
            $form .= '
<form id="ribbons_generate" name="ribbons_generate" class="ribbons generate" method="post"><fieldset>
    <input type="hidden" name="ribbons_task" value="generate"/>
    <input type="submit" value="> > Click here to generate your image. < <"/>
</fieldset></form>
    ';
        }
        $form .= '
<form id="ribbons_form" name="ribbons_form" class="ribbons" method="post"><fieldset>
    <input type="hidden" id="ribbons_task" name="ribbons_task" value="configure"/>';
        
    
        $form .= '
    <div class="box_container">
        <input type="submit" id="ribbons_submit" name="ribbons_submit" value="> > Click here to save. < <"/>';
        $form .= '
        <h2>Effects</h2>';
        foreach( static::$effects as $effect => $details ){
            $input_value = preg_replace('/\s+/','_',$effect);
            $type = $details[0];
            if( !empty($details[1]) ){
                $input_name = preg_replace('/\s+/','_',$details[1]);
            }else{
                $input_name = $input_value;
            }
            $checked = '';
            if(
                (
                    ! empty($_SESSION['ribbons']['post'][$input_name])
                    AND $_SESSION['ribbons']['post'][$input_name] === $input_value
                )
                OR(
                    empty($_SESSION['ribbons']['post'][$input_name])
                    AND( $effect === 'Ribbon' )
                )
            ){
                $checked .= 'checked="checked"';
            }
            $id = uniqid($input_name.'_');
            $form .= '
        <div class="effect input_box '.$input_name.'">
            <label for="'.$id.'" style="background-image:url(\'./KSP_images/ribbons/'.$effect.'.png\');">'.$effect.'</label>
            <input class="'.$input_name.'" type="'.$type.'" id="'.$id.'" name="'.$input_name.'" value="'.$input_value.'" '.$checked.'/>
        </div>';
        }
        $form .= '
        <hr/>';
        $form .= '
        <div class="ribbons_output" style="position:relative;">';
        $cols = array(
            array('Kerbol','Moho','Asteroid')
            ,array('Eve','Gilly','Eeloo')
            ,array('Kerbin','Mun','Minmus')
            ,array('Duna','Ike','Dres')
            ,array('Jool','Laythe','Vall')
            ,array('Tylo','Bop','Pol')
            ,array('Grand Tour')
        );
        foreach($cols as $column){
            $form .= '
            <div class="column">';
            foreach($column as $ribbon_name){
                $visited = false;
                $ribbon_class = preg_replace('/\s+/i','_',$ribbon_name);
                if( ! empty($_SESSION['ribbons']['post'][$ribbon_class.':visited']) ){
                    $visited = true;
                }
                $cell_class = 'cell '.$ribbon_class;
                $cell_layers = '';
                if( $ribbon_name === 'Asteroid' ){
                    $cell_image = 'none';
                    foreach( static::$asteroids as $asteroid_name ){
                        $layer_image = './KSP_images/ribbons/'.$asteroid_name.'.png';
                        if( ! is_readable($layer_image) ){ continue; }
                        $layer_class = preg_replace('/\s+/i','_',$asteroid_name);
                        if(
                            ! empty($_SESSION['ribbons']['post']['Asteroid'])
                            AND $_SESSION['ribbons']['post']['Asteroid'] === $layer_class
                        ){
                            $visited = true;
                            $layer_class .= ' selected';
                        }
                        $cell_layers .= '
                    <img class="layer asteroid '.$layer_class.'" alt="'.$layer_image.'" src="'.$layer_image.'"/>';
                    }
                }elseif( $ribbon_name === 'Grand Tour' ){
                    $cell_image = 'url(\'./KSP_images/ribbons/shield/Base Colours.png\')';
                }else{
                    $cell_image = 'url(\'./KSP_images/ribbons/'.$ribbon_name.'.png\')';
                }
                
                foreach( static::$effects as $effect => $details ){
                    if( $effect === 'None' ){ continue; }
                    $layer_class = preg_replace('/\s+/','_',$effect);
                    $input_type = $details[0];
                    if( !empty($details[1]) ){
                        $input_name = preg_replace('/\s+/','_',$details[1]);
                    }else{
                        $input_name = $layer_class;
                    }
                    if( $ribbon_name === 'Grand Tour' ){
                        $layer_image = './KSP_images/ribbons/shield/'.$effect.'.png';
                    }else{
                        $layer_image = './KSP_images/ribbons/'.$effect.'.png';
                    }
                    if(
                        (
                            ! empty($_SESSION['ribbons']['post'][$input_name])
                            AND $_SESSION['ribbons']['post'][$input_name] === $layer_class
                        )
                        OR(
                            empty($_SESSION['ribbons']['post'][$input_name])
                            AND( $effect === 'Ribbon' )
                        )
                    ){
                        $layer_class .= ' selected';
                    }
                    $cell_layers .= '
                    <img class="layer effect '.$input_type.' '.$layer_class.'" alt="'.$layer_image.'" src="'.$layer_image.'"/>';
                }
                
                $devices_ordered = array();
                $devices_UNordered = array();
                foreach( static::$devices as $key => $val ){
                    if( empty($val[0]) ){ continue; }
                    $priority = preg_filter('/^\D*(\d+)$/i','$1',$val[0]);
                    if( $priority !== null ){
                        $devices_ordered[$priority] = $key;
                    }else{
                        $devices_UNordered[] = $key;
                    }
                }
                foreach( $devices_UNordered as $val ){
                    $devices_ordered[] = $val;
                }
                ksort($devices_ordered);
                
                foreach( $devices_ordered as $device ){
                    if( $ribbon_name === 'Grand Tour' ){
                        $layer_image = './KSP_images/ribbons/shield/'.$device.'.png';
                    }else{
                        $layer_image = './KSP_images/ribbons/'.$device.'.png';
                    }
                    if( ! is_readable($layer_image) ){ continue; }
                    $ribbon_class = preg_replace('/\s+/i','_',$ribbon_name);
                    $input_name = preg_replace('/\s+/i','_',$ribbon_class.':'.$device);
                    $layer_class = preg_replace('/\s+/i','_',$device);
                    $input_value = $layer_class;
                    if(
                        ! empty($_SESSION['ribbons']['post'][$input_name])
                        OR(
                            ! empty($_SESSION['ribbons']['post'][$ribbon_class.':craft'])
                            AND $_SESSION['ribbons']['post'][$ribbon_class.':craft'] === $input_value
                        )
                    ){
                        $visited = true;
                        $layer_class .= ' selected';
                    }
                    $cell_layers .= '
                    <img class="layer device '.$layer_class.'" alt="'.$layer_image.'" src="'.$layer_image.'"/>';
                }
                
                if( $ribbon_name === 'Grand Tour' ){
                    $value = 0;
                    if( ! empty($_SESSION['ribbons']['post']['Grand_Tour:Orbit']) ){
                        $value = $_SESSION['ribbons']['post']['Grand_Tour:Orbit'];
                        $visited = true;
                    }
                    $i = 1; while( $i <= 15 ){
                        $suff = $i;
                        if( $i > 8 ){
                            $suff = ($i - 7).' Silver';
                        }
                        $layer_image = './KSP_images/ribbons/shield/Orbit '.$suff.'.png';
                        $layer_class = 'Orbit_'.$i;
                        if( $i <= $value ){
                            $layer_class .= ' selected';
                        }
                        $cell_layers .= '
                    <img class="layer device gt_orbit '.$layer_class.'" alt="'.$layer_image.'" src="'.$layer_image.'"/>';
                        $i++;
                    }
                    $value = 0;
                    if( ! empty($_SESSION['ribbons']['post']['Grand_Tour:Landing']) ){
                        $value = $_SESSION['ribbons']['post']['Grand_Tour:Landing'];
                        $visited = true;
                    }
                    $i = 1; while( $i <= 15 ){
                        $suff = $i;
                        if( $i > 8 ){
                            $suff = ($i - 7).' Silver';
                        }
                        $layer_image = './KSP_images/ribbons/shield/Landing '.$suff.'.png';
                        $layer_class = 'Landing_'.$i;
                        if( $i <= $value ){
                            $layer_class .= ' selected';
                        }
                        $cell_layers .= '
                    <img class="layer device gt_landing '.$layer_class.'" alt="'.$layer_image.'" src="'.$layer_image.'"/>';
                        $i++;
                    }
                    foreach( static::$planets as $planet => $attribs ){
                        if( $planet === 'Kerbol' OR $planet === 'Asteroid' ){ continue; }
                        $layer_image = './KSP_images/ribbons/shield/'.$planet.'.png';
                        if( ! is_readable($layer_image) ){
                            $layer_image = './KSP_images/ribbons/shield/'.$planet.'Visit.png';
                        }
                        if( ! is_readable($layer_image) ){
                            $layer_image = './KSP_images/ribbons/shield/'.$planet.' Visit.png';
                        }
                        $layer_class = preg_replace('/\s+/i','_',$planet);
                        if( ! empty($_SESSION['ribbons']['post']['Grand_Tour:'.$layer_class]) ){
                            $visited = true;
                            $layer_class .= ' selected';
                        }
                        $cell_layers .= '
                    <img class="layer device '.$layer_class.'" alt="'.$layer_image.'" src="'.$layer_image.'"/>';
                    }
                }
                
                
                $cell_style = 'background-image:'.$cell_image.';';
                $name_style = '';
                if( empty($visited) ){
                    $cell_style .= ' opacity:0.5;';
                }else{
                    $name_style .= 'display:none;';
                }
                $form .= '
                <div title="'.$ribbon_name.'" class="'.$cell_class.'" style="'.$cell_style.'">
                    <span class="name" style="'.$name_style.'">'.$ribbon_name.'</span>
                    '.$cell_layers.'
                </div>';
                
                // END each cell.
            }
            $form .= '
            </div>'; 
            
            // END each column.
        }
        $form .= '
            <div style="clear:both;"></div>
        </div>
    </div>';

        foreach( static::$planets as $planet => $attribs ){
            $planet_class = preg_replace('/\s+/i','_',$planet);
            $form .= '
    <div class="planet '.$planet_class.' box_container" name="'.$planet.'">
        <h2 class="title">'.$planet;
            if( $planet === 'Kerbol' ){ $form .= ' (The Sun)'; }
            if( $planet === 'Asteroid' ){ $form .= ' (Choose one.)'; }
            $form .= '</h2>';
            
            // Asteroid?
            if( $planet === 'Asteroid' ){
                $add = '';
                if(
                    empty($_SESSION['ribbons']['post'][$planet_class])
                    OR(
                        ! empty($_SESSION['ribbons']['post'][$planet_class])
                        AND $_SESSION['ribbons']['post'][$planet_class] === 'None'
                    )
                ){
                    $add = ' checked="checked"';
                }
                $form .= '
        <div class="asteroid input_box">
            <label for="asteroid_none">None</label>
            <input type="radio" id="asteroid_none" name="'.$planet_class.'"'.$add.' value="None"/>
        </div>';
                foreach( static::$asteroids as $asteroid ){
                    $asteroid_class = preg_replace('/\s+/i','_',$asteroid);
                    $add = '';
                    if(
                        ! empty($_SESSION['ribbons']['post'][$planet_class])
                        AND $_SESSION['ribbons']['post'][$planet_class] === $asteroid_class
                    ){
                        $add .= ' checked="checked"';
                    }
                    $form .= '
        <div class="asteroid input_box">
            <label for="'.$asteroid_class.'" style="background-image:url(\'./KSP_images/ribbons/'.$asteroid.'.png\')
            ">'.$asteroid.'</label>
            <input type="radio" id="'.$asteroid_class.'" name="'.$planet_class.'"'.$add.' value="'.$asteroid_class.'"/>
        </div>';
                }
            }else{
                $add = '';
                if( ! empty($_SESSION['ribbons']['post'][$planet_class.':visited']) ){
                    $add .= ' checked="checked"';
                }
                $form .= '
        <div class="visited input_box">
            <label for="'.$planet_class.':visited" style="background-image:url(\'./KSP_images/ribbons/icons/'.$planet.'.png\')">Visited</label>
            <input class="visited" type="checkbox" id="'.$planet_class.':visited" name="'.$planet_class.':visited"'.$add.'/>
        </div>';
            }
            
            // Maneuvers (mans)
            
            $form .= '
        <div style="clear:both;"></div>
        <div><strong>Maneuvers</strong> <small>(Check all that apply.)</small></div>
        ';
            foreach( static::$devices['mans'] as $device => $desc ){
                $name = $planet.':'.$device;
                $form .= add_device('checkbox', $name, $device, $desc);
            }
            foreach( static::$devices['mans:spec'] as $device => $desc ){
                $name = $planet.':'.$device;
                if(
                    ($device === 'Geosynchronous' AND $attribs['sync'])
                    OR ($device === 'Kerbol Escape' AND $planet === 'Kerbol')
                ){
                    $form .= add_device('checkbox', $name, $device, $desc);
                }
            }
            if( $attribs['surf'] ){
                foreach( static::$devices['mans:surf'] as $device => $desc ){
                    $name = $planet.':'.$device;
                    $form .= add_device('checkbox', $name, $device, $desc);
                }
            }
            if( $attribs['atmo'] ){
                foreach( static::$devices['mans:atmo'] as $device => $desc ){
                    $name = $planet.':'.$device;
                    $form .= add_device('checkbox', $name, $device, $desc);
                }
            }
            
            // Crafts
            
            $form .= '
        <div style="clear:both;"></div>
        <div><strong>Craft</strong> <small>(Choose one.)</small></div>';
            $name = $planet.':craft';
            $form .= add_device('radio', $name, 'None');
            foreach( static::$devices['crafts'] as $device => $desc ){
                $form .= add_device('radio', $name, $device, $desc);
            }
            if( $attribs['surf'] ){
                $form .= '
        <div style="clear:both;"></div>';
                foreach( static::$devices['crafts:surf'] as $device => $desc ){
                    $form .= add_device('radio', $name, $device, $desc);
                }
            }
            if( $attribs['atmo'] OR $planet === 'Kerbol' ){
                $form .= '
        <div style="clear:both;"></div>';
                foreach( static::$devices['crafts:atmo'] as $device => $desc ){
                    $form .= add_device('radio', $name, $device, $desc);
                }
            }
            foreach( static::$devices['crafts:spec'] as $device => $desc ){
                $form .= '
        <div style="clear:both;"></div>';
                if(
                    ($device === 'Extreme EVA' AND $attribs['eeva'])
                ){
                    $form .= add_device('radio', $name, $device, $desc);
                }
            }
            
            // Specials
            $form .= '
        <div style="clear:both;"></div>
        <div><strong>Special</strong> <small>(Check all that apply.)</small></div>';
            foreach( static::$devices['misc'] as $device => $desc ){
                $name = $planet.':'.$device;
                $form .= add_device('checkbox', $name, $device, $desc);
            }
            foreach( static::$devices['misc:spec'] as $device => $desc ){
                $name = $planet.':'.$device;
                if(
                    ($device === 'Anomaly' AND $attribs['anom'])
                    OR ($device === 'Challenge Wreath' AND $attribs['chal'])
                ){
                    if( $device === 'Challenge Wreath' ){
                        switch($planet){
                            case 'Eve':
                                $desc .= 'Eve Challenge: Land safely, then launch and achieve orbit.';
                            break;
                            case 'Tylo':
                                $desc .= 'Tylo Challenge: Land safely, then launch and achieve orbit.';
                            break;
                            case 'Kerbin':
                                $desc .= 'Kerbin Challenge: Single Stage To Orbit (SSTO) - Achieve orbit without dropping any parts other than launch clamps.';
                            break;
                        }
                    }
                    $form .= add_device('checkbox', $name, $device, $desc);
                }
            }

            
            $form .= '
        <div style="clear:both;"></div>
    </div>';
        }
        
        // Grand Tour gets different stuff.
        $checked = '';
        if( ! empty($_SESSION['ribbons']['post']['Grand_Tour:visited']) ){
            $checked .= ' checked="checked"';
        }
        $form .= '
<div class="planet Grand_Tour box_container" name="Grand Tour">
    <h2 class="title">Grand Tour</h2>';
        $form .= '
        <div class="visited input_box Grand_Tour:visited">
            <label for="Grand_Tour:visited" style="background-image:none">Achieved</label>
            <input class="visited" type="checkbox" id="Grand_Tour:visited" name="Grand_Tour:visited"'.$checked.'/>
        </div>';
        
        $form .= '
        <div class="device input_box Grand_Tour:Orbit" title="">
            <label for="Grand_Tour:Orbit">Grand Tour Orbits</label>
            <select id="Grand_Tour:Orbit" name="Grand_Tour:Orbit">';
        $value = 0;
        if( ! empty($_SESSION['ribbons']['post']['Grand_Tour:Orbit']) ){
            $value = $_SESSION['ribbons']['post']['Grand_Tour:Orbit'];
        }
        $i = 0; while( $i <= 15 ){
            $selected = '';
            if( $i == $value ){ $selected = ' selected="selected"'; }
            $form .= '
                <option'.$selected.'>'.$i.'</option>';
            $i++;
        }
        $form .= '
            </select>
        </div>';
        
        $form .= '
        <div class="device input_box Grand_Tour:Landing" title="">
            <label for="Grand_Tour:Landing">Grand Tour Landings</label>
            <select id="Grand_Tour:Landing" name="Grand_Tour:Landing">';
        $value = 0;
        if( ! empty($_SESSION['ribbons']['post']['Grand_Tour:Landing']) ){
            $value = $_SESSION['ribbons']['post']['Grand_Tour:Landing'];
        }
        $i = 0; while( $i <= 15 ){
            $selected = '';
            if( $i == $value ){ $selected = ' selected="selected"'; }
            $form .= '
                <option'.$selected.'>'.$i.'</option>';
            $i++;
        }
        $form .= '
            </select>
        </div>';
        
        $form .= '
        <div style="clear:both;"></div>
        <div><strong>Lives</strong> <small>(Check all that apply.)</small></div>
        ';
        $form .= add_device('checkbox', 'Grand_Tour:Kerbal_Lost', 'Kerbal Lost', static::$devices['Kerbal Lost'][1]);
        $form .= add_device('checkbox', 'Grand_Tour:Kerbal_Saved', 'Kerbal Saved', static::$devices['Kerbal Saved'][1]);
        
        $form .= '
        <div style="clear:both;"></div>
        <div><strong>Craft</strong> <small>(Choose one.)</small></div>
        ';
        
        $gt_crafts = array(
            'Aircraft','Capsule','Lander','Multi-Part Ship','Probe Lander','Probe Rover','Probe','Rover'
        );
        $name = 'Grand_Tour:craft';
        $form .= add_device('radio', $name, 'None');
        foreach( $gt_crafts as $craft ){
            $form .= add_device('radio', $name, $craft, static::$devices[$craft][1]);
        }
        
        $form .= '
        <div style="clear:both;"></div>
        <div><strong>Planets Visited</strong> <small>(Check all that apply.)</small></div>
        ';
        foreach( static::$planets as $planet => $attribs ){
            if( $planet === 'Kerbol' OR $planet === 'Asteroid' ){ continue; }
            $form .= add_device('checkbox', 'Grand_Tour:'.$planet, $planet, null, 'gt_planet');
        }
        
        $form .= '
    <div style="clear:both;"></div>
</fieldset></form>';
        return $form;
    }
    
    // END of class
}
?>