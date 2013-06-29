<?php
/* 
 * This will display social link
 * 
 */
?>
<div class="connections">
    <button class="facebook" onClick="parent.location='/connect_with/provider:Facebook'"><?php echo __('Sign-in with Facebook'); ?></button>
    <button class="twitter" onClick="parent.location='/connect_with/provider:Twitter'"><?php echo __('Sign-in with Twitter'); ?></button>
    <button class="google" onClick="parent.location='/connect_with/provider:Google'"><?php echo __('Sign-in with Google'); ?></button>
    <button class="open_id" onClick="parent.location='/connect_with/provider:OpenID'"><?php echo __('Sign-in with Open Id'); ?></button>
    <button class="yahoo" onClick="parent.location='/connect_with/provider:Yahoo'"><?php echo __('Sign-in with Yahoo'); ?></button>
    <button class="aol" onClick="parent.location='/connect_with/provider:AOL'"><?php echo __('Sign-in with AOL'); ?></button>
</div>