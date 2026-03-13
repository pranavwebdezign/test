<?php
// Not like register_uninstall_hook(), you do NOT have to use a static function.
dcf_fs()->add_action('after_uninstall', 'dcf_fs_uninstall_cleanup');
