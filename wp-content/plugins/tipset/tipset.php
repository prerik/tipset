<?php

/*
  Plugin Name: Mästerskapstipset
  Plugin URI:
  Description: Plugin för hantering av mästerskapstipset
  Version: 1.0
  Author: Per-Erik Eriksson
  Author URI:
  License:
 */
include_once 'top-list-widget.php';
include_once 'bottom-list-widget.php';
include_once 'tipset-settings.php';

register_activation_hook(__FILE__, 'tipset_install');
add_action('wp_head', 'add_css');
add_action('widgets_init', 'load_widgets');
add_option('tipset_open', 'true');
add_action('admin_menu', 'tipset_plugin_menu');

function load_widgets() {
    register_widget("TopListWidget");
    register_widget("BottomListWidget");
    //registrera fler här om man vill...
}

function add_css() {
    echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/tipset/css/style.css" />' . "\n";
}

function user_tips_func($atts) {
    global $wpdb;
    $options = get_option('tipset_options');


    $user = "";
    if (isset($_GET['id']) && $options['tipset_open'] != "on") {
        $user = $_GET['id'];
    } else if (is_user_logged_in()) {
        $user = get_current_user_id();
    }
    $user_info = get_userdata($user);

    if (isset($_POST['home-1'])) { //formuläret är submittat
        updateTips($user);
    }

    $querystring = "SELECT
matches.id as mid,
matches.result_home_team,
matches.result_away_team,
home_team.name as home_team,
away_team.name as away_team,
tips.goals_home_team,
tips.goals_away_team,
tips.sign,
results.points
FROM wp_tips_matches as matches
LEFT JOIN wp_tips_teams AS home_team ON matches.home_team=home_team.id
LEFT JOIN wp_tips_teams AS away_team ON matches.away_team=away_team.id
LEFT JOIN wp_tips_tips as tips ON matches.id=tips.matches_id AND tips.users_id = '" . $user . "'
LEFT JOIN wp_tips_results as results ON matches.id=results.matches_id AND results.users_id = '" . $user . "' ORDER BY matches.id";
    $tips = $wpdb->get_results($querystring, OBJECT_K);
    $result = "<h2>Tipsrad för: " . $user_info->user_firstname . " " . $user_info->user_lastname . "</h2>";
    $result .= "<form id=\"tips-form\" method=\"post\" action=\"\">";
    $result .= "<table>";
    $result .= "<tr>";
    $result .= "<th>Matchnr</th>";
    $result .= "<th>Hemmalag</th>";
    $result .= "<th>Bortalag</th>";
    $result .= "<th colspan=\"3\">Resultat</th>";
    $result .= "<th>Tecken</th>";
    $result .= "<th colspan=\"3\">Slutresultat</th>";
    $result .= "<th>Poäng</th>";
    $result .= "</tr>";
    foreach ($tips as $t) {
        $result .= "<tr>";
        $result .= "<td>" . $t->mid . "</td>";
        $result .= "<td>" . $t->home_team . "</td>";
        $result .= "<td>" . $t->away_team . "</td>";
        if ($options['tipset_open'] == on) {
            $result .= "<td><input type=\"text\" name=\"home-" . $t->mid . "\" value=\"" . $t->goals_home_team . "\" /></td>";
            $result .= "<td>-</td>";
            $result .= "<td><input type=\"text\" name=\"away-" . $t->mid . "\" value=\"" . $t->goals_away_team . "\" /></td>";
            $result .= "<td><input type=\"text\" name=\"sign-" . $t->mid . "\" value=\"" . $t->sign . "\" /></td>";
        } else {
            $result .= "<td>" . $t->goals_home_team . "</td>";
            $result .= "<td>-</td>";
            $result .= "<td>" . $t->goals_away_team . "</td>";
            $result .= "<td>" . $t->sign . "</td>";
        }
        $result .= "<td>" . $t->result_home_team . "</td>";
        $result .= "<td>-</td>";
        $result .= "<td>" . $t->result_away_team . "</td>";
        $result .= "<td>" . $t->points . "</td>";
        $result .= "</tr>";
    }
    $result .= "</table>";
    $result .= "<input style=\"margin-left:200px;width: 150px;\" type=\"submit\" value=\"Spara ändringar\">";
    $result .= "</form>";
    return $result;
}

add_shortcode('tips', 'user_tips_func');

//[results]
function results_func($atts) {
    global $wpdb;
    $result = "";
    if ($_GET['userid'] != '') {
        $querystring = "SELECT firstname.meta_value as first_name, lastname.meta_value as last_name FROM wp_users ".
                "LEFT JOIN wp_usermeta as firstname ON wp_users.id=firstname.user_id AND firstname.meta_key='first_name' ".
                "LEFT JOIN wp_usermeta as lastname ON wp_users.id=lastname.user_id AND lastname.meta_key='last_name' WHERE wp_users.id = ". $_GET['userid'];
        $tippers = $wpdb->get_results($querystring, OBJECT_K);
        foreach ($tippers as $tipper) {
            $result .= "<h3>Tips för ". $tipper->first_name . " " . $tipper->last_name ."</h3>";
        }
        $result .= "<p><a href=\"/resultat\">Tillbaka till resultat</a></p>";
        $querystring = "SELECT tips.matches_id as matchid, home_team.name as home_team, away_team.name as away_team, tips.goals_home_team as home_goals, tips.goals_away_team as away_goals, tips.sign as sign, ".
                "(SELECT sum(points) FROM wp_tips_results WHERE users_id = tips.users_id AND matches_id=tips.matches_id) as points ".
                "FROM wp_tips_tips as tips ".
                "LEFT JOIN wp_tips_matches as matches ON tips.matches_id=matches.id ".
                "LEFT JOIN wp_tips_teams as home_team ON matches.home_team=home_team.id ".
                "LEFT JOIN wp_tips_teams as away_team ON matches.away_team=away_team.id WHERE tips.users_id = ". $_GET['userid'];
        $results = $wpdb->get_results($querystring, OBJECT_K);
        $result .= "<table>";
        $result .= "<tr>";
        $result .= "<th align='left' colspan='4'>Match</th>";
        $result .= "<th colspan='3'>Resultat</th>";
        $result .= "<th>Tecken</th>";
        $result .= "<th>Poäng</th>";
        $result .= "</tr>";
        foreach ($results as $r) {
            $result .= "<tr>";
            $result .= "<td>";
            $result .= $r->matchid;
            $result .= "</td>";
            $result .= "<td>";
            $result .= $r->home_team;
            $result .= "</td>";
            $result .= "<td>";
            $result .= " - ";
            $result .= "</td>";
            $result .= "<td>";
            $result .= $r->away_team;
            $result .= "</td>";
            $result .= "<td>";
            $result .= $r->home_goals;
            $result .= "</td>";
            $result .= "<td>";
            $result .= " - ";
            $result .= "</td>";
            $result .= "<td>";
            $result .= $r->away_goals;
            $result .= "</td>";
            $result .= "<td>";
            $result .= $r->sign;
            $result .= "</td>";
            $result .= "<td>";
            $result .= $r->points;
            $result .= "</td>";
            $result .= "</tr>";
        }
        $result .= "</table>";
    } else {
        $querystring = "SELECT results.users_id as userid, CONCAT(first_name.meta_value, ' ', last_name.meta_value) AS namn, SUM(results.points) AS poang " .
                "FROM wp_tips_results AS results " .
                "LEFT JOIN wp_usermeta AS first_name ON results.users_id=first_name.user_id AND first_name.meta_key='first_name' " .
                "LEFT JOIN wp_usermeta AS last_name ON results.users_id=last_name.user_id AND last_name.meta_key='last_name' WHERE results.users_id NOT IN (1,15,30,52,21,19,40,8,23,16,41,54,24,36,31,37,47,38,20,32) " .
                "GROUP BY results.users_id ORDER BY poang DESC, namn ASC";
        $results = $wpdb->get_results($querystring, OBJECT_K);
        $result = "<table>";
        $result .= "<tr>";
        $result .= "<th align='left'>Tippare</th>";
        $result .= "<th>Poäng</th>";
        $result .= "</tr>";
        foreach ($results as $r) {
            $result .= "<tr>";
            $result .= "<td>";
            $result .= "<a href='?userid=". $r->userid ."'>";
            $result .= $r->namn;
            $result .= "</a>";
            $result .= "</td>";
            $result .= "<td>";
            $result .= $r->poang;
            $result .= "</td>";
            $result .= "</tr>";
        }
        $result .= "</table>";
    }
    return $result;
}

add_shortcode('results', 'results_func');

function matches_func($atts) {
    $result = "";
    global $wpdb;
    if ($_GET['matchid'] != '') {
        $querystring = "SELECT home_team.name as home_team, away_team.name as away_team FROM wp_tips_matches LEFT JOIN wp_tips_teams as home_team ON wp_tips_matches.home_team=home_team.id LEFT JOIN wp_tips_teams as away_team ON wp_tips_matches.away_team=away_team.id WHERE wp_tips_matches.id = ". $_GET['matchid'];
        $matches = $wpdb->get_results($querystring, OBJECT_K);
        foreach ($matches as $match) {
            $result .= "<h3>". $match->home_team . " - " . $match->away_team . "</h3>";
        }
        $result .= "<p><a href=\"/matcher\">Tillbaka till matcher</a></p>";
        $querystring = "SELECT wp_users.`ID` as user_id, firstname.meta_value as first_name, lastname.meta_value as last_name, tips.`goals_home_team` as goals_home, tips.`goals_away_team` as goals_away, tips.`sign` as sign FROM wp_users ".
"LEFT JOIN wp_tips_tips AS tips ON wp_users.id=tips.users_id AND tips.matches_id=". $_GET['matchid'].
" LEFT JOIN wp_usermeta AS lastname ON wp_users.id=lastname.user_id AND lastname.`meta_key`='last_name' ".
"LEFT JOIN wp_usermeta AS firstname ON wp_users.id=firstname.user_id AND firstname.`meta_key`='first_name' WHERE wp_users.`ID` NOT IN (1,15,30,52,21,19,40,8,23,16,41,54,24,36,31,37,47,38,20,32)".
"ORDER BY lastname.meta_value";
        $tips = $wpdb->get_results($querystring, OBJECT_K);
        $result .= "<table>";
        $result .= "<tr>";
        $result .= "<th>Tippare</th>";
        $result .= "<th colspan='3'>Resultat</th>";
        $result .= "<th>Tecken</th>";
        $result .= "</tr>";
        foreach ($tips as $tip) {
            $result .= "<tr>";
            $result .= "<td>";
            $result .= $tip->first_name . " " . $tip->last_name;
            $result .= "</td>";
            $result .= "<td>";
            $result .= $tip->goals_home;
            $result .= "</td>";
            $result .= "<td> - </td>";
            $result .= "<td>";
            $result .= $tip->goals_away;
            $result .= "</td>";
            $result .= "<td>";
            $result .= $tip->sign;
            $result .= "</td>";
            $result .= "</tr>";
        }
        $result .= "</table>";
        
    } else {
        if ($_GET['updated'] == 'true') {
            updateResults();
        }
        $querystring = "SELECT matches.id, home_team.name as home_team, away_team.name as away_team, matches.start_time, matches.result_home_team, matches.result_away_team FROM wp_tips_matches as matches " .
                "LEFT JOIN wp_tips_teams as home_team ON matches.home_team=home_team.id " .
                "LEFT JOIN wp_tips_teams as away_team ON matches.away_team=away_team.id ORDER BY matches.id";
        $matches = $wpdb->get_results($querystring, OBJECT_K);
        setlocale('LC_ALL', 'sv_SE.UTF-8');
        if (current_user_can('manage_options')) {
            $result .= "<form method=\"post\" action=\"?updated=true\" />";
        }
        $result .= "<table>";
        $result .= "<tr>";
        $result .= "<th>Matchnr</th>";
        $result .= "<th>Hemmalag</th>";
        $result .= "<th>Bortalag</th>";
        $result .= "<th>Datum/tid</th>";
        $result .= "<th colspan=\"3\">Slutresultat</th>";
        $result .= "</tr>";
        foreach ($matches as $match) {
            $result .= "<tr>";
            $result .= "<td>";
            $result .= "<a href=\"?matchid=". $match->id  ."\">";
            $result .= $match->id;
            $result .= "</a>";
            $result .= "</td>";
            $result .= "<td>";
            $result .= "<a href=\"?matchid=". $match->id  ."\">";
            $result .= $match->home_team;
            $result .= "</a>";
            $result .= "</td>";
            $result .= "<td>";
            $result .= "<a href=\"?matchid=". $match->id  ."\">";
            $result .= $match->away_team;
            $result .= "</a>";
            $result .= "</td>";
            $result .= "<td>";
            $result .= strftime("%a %e %b %H:%M", strtotime($match->start_time));
            $result .= "</td>";
            $result .= "<td>";
            if (current_user_can('manage_options')) {
                $result .= "<input style=\"width: 20px;\" type=\"text\" name=\"home-" . $match->id . "\" value=\"" . $match->result_home_team . "\" />";
            } else {
                $result .= $match->result_home_team;
            }
            $result .= "</td>";
            $result .= "<td>-</td>";
            $result .= "<td>";
            if (current_user_can('manage_options')) {
                $result .= "<input style=\"width: 20px;\" type=\"text\" name=\"away-" . $match->id . "\" value=\"" . $match->result_away_team . "\" />";
            } else {
                $result .= $match->result_away_team;
            }
            $result .= "</td>";
            $result .= "</tr>";
        }
        $result .= "</table>";
        if (current_user_can('manage_options')) {
            $result .= "<input type=\"submit\" value=\"Spara ändringar\" style=\"margin-left: 150px;width: 200px;\">";
            $result .= "</form>";
        }
    }
    return $result;
}

add_shortcode('matches', 'matches_func');

function updateResults() {
    global $wpdb;
    $sql = "select id from wp_tips_matches";
    $matches = $wpdb->get_results($sql, OBJECT_K);
    foreach ($matches as $m) {
        if ($_POST['home-' . $m->id] != '' &&
                $_POST['away-' . $m->id] != '') {
            $wpdb->update($wpdb->prefix . "tips_matches", array(
                'result_home_team' => $_POST['home-' . $m->id],
                'result_away_team' => $_POST['away-' . $m->id],
                    ), array(
                'id' => $m->id));
        }
    }

    //uppdatera resultattabell
    $wpdb->query("DELETE FROM wp_tips_results");
    $sql = "SELECT id, result_home_team, result_away_team FROM wp_tips_matches WHERE result_home_team IS NOT NULL AND result_away_team IS NOT NULL";
    $matches = $wpdb->get_results($sql, OBJECT_K);
    foreach ($matches as $m) {
        $users = $wpdb->get_results("SELECT wp_users.id, tips.goals_home_team as tipH, tips.goals_away_team as tipA, tips.sign as tipS from wp_users LEFT JOIN wp_tips_tips as tips ON wp_users.id=tips.users_id AND tips.matches_id = $m->id", OBJECT_K);
        foreach ($users as $u) {
            $points = calculatePoints($m->result_home_team, $m->result_away_team, $u->tipH, $u->tipA, $u->tipS);
            $wpdb->insert($wpdb->prefix . "tips_results", array(
                'users_id' => $u->id,
                'matches_id' => $m->id,
                'points' => $points));
        }
    }
}

function calculatePoints($resultH, $resultA, $tipH, $tipA, $tipS) {
    $points = 0;
    $resultS = "";
    $tipS = strtolower($tipS);
    if ($resultH == $tipH &&
            $resultA == $tipA) {
        if (intval($resultH) + intval($resultA) > 2) {
            $points = $points + 4;
        } else {
            $points = $points + 2;
        }
    }
    if (intval($resultH) > intval($resultA)) {
        $resultS = "1";
    } else if (intval($resultH) < intval($resultA)) {
        $resultS = "2";
    } else if (intval($resultH) == intval($resultA)) {
        $resultS = "x";
    }
    if ($tipS == $resultS) {
        $points = $points + 1;
    }
    return $points;
}

function tipset_install() {
    global $wpdb;

    $matches_table_name = $wpdb->prefix . "tips_matches";

    $matches_sql = "CREATE TABLE " . $matches_table_name . " (
	  id int NOT NULL AUTO_INCREMENT,
	  home_team varchar(10) DEFAULT '' NOT NULL,
	  away_team varchar(10) DEFAULT '' NOT NULL,
	  result_home_team int,
	  result_away_team int,
	  start_time datetime,
	  UNIQUE KEY id (id)
	);";

    $results_table_name = $wpdb->prefix . "tips_results";

    $results_sql = "CREATE TABLE " . $results_table_name . " (
	  users_id int NOT NULL,
	  matches_id int NOT NULL,
	  points int,
	  UNIQUE KEY id (users_id, matches_id)
	);";

    $teams_table_name = $wpdb->prefix . "tips_teams";

    $teams_sql = "CREATE TABLE " . $teams_table_name . " (
	  id varchar(10) NOT NULL,
	  name varchar(255),
	  UNIQUE KEY id (id)
	);";

    $tips_table_name = $wpdb->prefix . "tips_tips";

    $tips_sql = "CREATE TABLE " . $tips_table_name . " (
	  users_id int NOT NULL,
	  matches_id int NOT NULL,
	  goals_away_team int NOT NULL,
	  goals_home_team int NOT NULL,
	  sign char(1) NOT NULL,
	  UNIQUE KEY id (users_id, matches_id)
	);";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($matches_sql);
    dbDelta($results_sql);
    dbDelta($teams_sql);
    dbDelta($tips_sql);
}

function updateTips($userId) {
    global $wpdb;
    $sql = "select id from wp_tips_matches";
    $matches = $wpdb->get_results($sql, OBJECT_K);
    //mysql_query("delete from tips where users_id = '". $_SESSION['user'] ."'");
    foreach ($matches as $match) {
        if ($_POST['home-' . $match->id] != '' &&
                $_POST['away-' . $match->id] != '' &&
                $_POST['sign-' . $match->id] != '') {
            $sql = "SELECT * FROM wp_tips_tips WHERE users_id = " . $userId . " AND matches_id = " . $match->id;
            if ($wpdb->get_row($sql, ARRAY_A)) {
                //update
                $wpdb->update($wpdb->prefix . "tips_tips", array(
                    'goals_home_team' => $_POST['home-' . $match->id],
                    'goals_away_team' => $_POST['away-' . $match->id],
                    'sign' => $_POST['sign-' . $match->id]), array(
                    'users_id' => $userId,
                    'matches_id' => $match->id));
            } else {
                //insert
                $wpdb->insert($wpdb->prefix . "tips_tips", array(
                    'users_id' => $userId,
                    'matches_id' => $match->id,
                    'goals_home_team' => $_POST['home-' . $match->id],
                    'goals_away_team' => $_POST['away-' . $match->id],
                    'sign' => $_POST['sign-' . $match->id]));
            }
            //$sql = "insert into tips (users_id, matches_id, goals_home_team, goals_away_team, sign) values ('". 
            //$_SESSION['user'] ."', ". $row['id'] .", ". $_POST['home-'.$row['id']]  .", ". $_POST['away-'.$row['id']] .", '". $_POST['sign-'.$row['id']] ."')";
            //mysql_query($sql);
            //  echo $sql."\n";
        }
    }
}

//if ($_GET['submit'] == "tips") {
//    $dbresult = mysql_query("select id from matches");
//    mysql_query("delete from tips where users_id = '". $_SESSION['user'] ."'");
//    while ($row =  mysql_fetch_array($dbresult)) {
//        $sql = "insert into tips (users_id, matches_id, goals_home_team, goals_away_team, sign) values ('". $_SESSION['user'] ."', ". $row['id'] .", ". $_POST['home-'.$row['id']]  .", ". $_POST['away-'.$row['id']] .", '". $_POST['sign-'.$row['id']] ."')";
//        mysql_query($sql);
//        //  echo $sql."\n";
//    }
//}
?>
