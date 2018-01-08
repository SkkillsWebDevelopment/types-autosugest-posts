# types-autosugest-posts
This adds an auto suggest for a text field created with types plugin. it saves the post id in the database and shows the post name in the editor.

You jave to edit the main file and adjust the post name and the post type on line 27 $field_list['suggested-product-code'] = array("selector" => '[name="wpcf[suggested-product-code]"]', "post_types" => array("produkter"));

You can add multiple fields and multiple post types
