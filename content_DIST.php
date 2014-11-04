<?php

$site = array(
    'title' => 'Oldschool Web Kit'
    ,'description' => 'A simple php web app.'
    ,'author' => 'Swiftek, Lc., www.Swiftek.net'
    ,'keywords' => 'webapp,web,site,app,application,builder,php,sqlite'
    ,'style' => ''
    ,'contact_name' => 'Swiftek'
    ,'contact_email' => 'Service2@Swiftek.net'
    ,'copyright_predate' => '&copy; '
    ,'copyright_postdate' => ' Swiftek, Lc.'
    ,'vanity' => 'Built by Swiftek&reg;'
    ,'header' => '
<div class="menu" style="text-align:center; font-size:x-large;">
    <a title="Home Page" href="./">Home</a>
    <a title="User Account Controls" href="?page=user">Users</a>
    <a title="Ribbon Generator" href="?page=ribbons">Ribbons</a>
    <a title="Send us an email." href="?page=contact">Contact Us</a>
    <div style="clear:both;"></div>
</div>'
    ,'content' => '
'
    ,'footer' => '
<h4>A simple php web kit.</h5>
<small>For more information, please Contact Us.</small>'
);



$pages = array(
    array(
        'page' => 'home'
        ,'title' => ''
        ,'description' => ''
        ,'author' => ''
        ,'keywords' => ''
        ,'style' => ''
        ,'header' => '
<h3>Welcome!</h3>'
        ,'content' => '
<p>The Oldschool Web Kit is a basic app for building a website in PHP.</p>
<p>Features:</p>
<ul>
    <li>Simple, modern, developer-friendly PHP framework.</li>
    <li>Produces a W3C compliant, HTML5 website, ready for content.</li>
    <li>Ideal for basic sites, or as a starting point.</li>
    <li>Employs PHP\'s embedded SQLite database - serverless, self-contained, and light-weight.</li>
    <li>User accounts with encryption, persistent login, automated registration and recovery.</li>
    <li>Email contact form with file attachments and anti-spam check.</li>
</ul>
'
        ,'footer' => ''
    )
    
    
    
    ,array(
        'page' => 'user'
        ,'title' => 'Account Controls'
        ,'description' => ''
        ,'author' => ''
        ,'keywords' => ''
        ,'style' => ''
        ,'header' => '
<h3>User Account Control</h3>'
        ,'content' => '
<p>The Oldschool Web Kit includes this user account manager.</p>
<p>Features:</p>
<ul>
    <li>Automated registration and account recovery via email.</li>
    <li>"Remember Me" login option via persistent cookies.</li>
    <li>Always encrypts sensitive information.</li>
    <li>Enforces optional levels of strictness.</li>
    <li>Input scrubbing and prepared statements deter XSS and SQL injection attacks.</li>
    <li>IP-based rate limiting deters brute force and DOS attacks.</li>
</ul>'
        ,'footer' => ''
    )
    
    
    
    ,array(
        'page' => 'contact'
        ,'title' => 'Contact'
        ,'description' => ''
        ,'author' => ''
        ,'keywords' => ''
        ,'style' => ''
        ,'header' => '
<h3>Contact Us</h3>'
        ,'content' => '
<p>The Oldschool Web Kit includes this email contact form.</p>
<p>Features:</p>
<ul>
    <li>Accepts file attachments.</li>
    <li>Anti-spam test question (customizable).</li>
</ul>'
        ,'footer' => ''
    )
    
    
    
    ,array(
        'page' => 'ribbons'
        ,'title' => 'Ribbons'
        ,'description' => ''
        ,'author' => ''
        ,'keywords' => ''
        ,'style' => ''
        ,'header' => '
<h3>Ribbon Generator</h3>'
        ,'content' => '
<p>Use this form to select your accomplishments.</p>'
        ,'footer' => ''
    )
);


?>