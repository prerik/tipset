<?php

class BottomListWidget extends WP_Widget {

    public function __construct() {
        parent::__construct(
                'bottomlistwidget', // Base ID
                'Tipset bottenlista', // Name
                array('description' => __('Widget för att visa bottenlista', 'text_domain'),) // Args
        );
    }

    public function form($instance) {
        /* Set up some default widget settings. */
        $defaults = array('title' => 'Bottenlistan');
        $instance = wp_parse_args((array) $instance, $defaults);

        echo "<p>";
        echo "<label for=\"" . $this->get_field_id('title') . "\">Titel:</label>";
        echo "<input id=\"" . $this->get_field_id('title') . "\" name=\"" . $this->get_field_name('title') . "\" value=\"" .
        $instance['title'] . "\" style=\"width:100%;\" />";
        echo "</p>";
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;

        /* Strip tags (if needed) and update the widget settings. */
        $instance['title'] = strip_tags($new_instance['title']);

        return $instance;
    }

    public function widget($args, $instance) {
        extract($args);
        $title = apply_filters('widget_title', $instance['title']);

        echo $before_widget;
        if (!empty($title))
            echo $before_title . $title . $after_title;
        echo $this->getContent();
        echo $after_widget;
    }

    function getContent() {
        global $wpdb;
        $querystring = "SELECT results.users_id as userid, CONCAT(first_name.meta_value, ' ', last_name.meta_value) AS namn, SUM(results.points) AS poang " .
                "FROM wp_tips_results AS results " .
                "LEFT JOIN wp_usermeta AS first_name ON results.users_id=first_name.user_id AND first_name.meta_key='first_name' " .
                "LEFT JOIN wp_usermeta AS last_name ON results.users_id=last_name.user_id AND last_name.meta_key='last_name' " .
				"WHERE results.users_id IN (SELECT DISTINCT users_id FROM wp_tips_tips) ".
                "GROUP BY results.users_id ORDER BY poang ASC, last_name.meta_value ASC LIMIT 5";
        $results = $wpdb->get_results($querystring, OBJECT_K);
        $result = "<ul>";
        foreach ($results as $r) {
            $result .= "<li>";
            $result .= "<a href=\"/resultat?userid=". $r->userid ."\">";
            $result .= $r->namn;
            $result .= "</a>";
            $result .= " (";
            $result .= $r->poang;
            $result .= ")";
            $result .= "</li>";
        }
        $result .= "</ul>";
        $result .= "<a style=\"margin-left: 5px;\" href=\"/resultat\">Visa alla resultat »</a>";
        return $result;
    }

}
?>


