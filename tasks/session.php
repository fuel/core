<?
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.1
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 *
 */

namespace Fuel\Tasks;

/**
 * Sessions DB Table Task
 *
 * @PullRequest https://github.com/fuel/core/pull/786
 *
 * Run this task to set add/remove/clear the db sessions table
 * Table name will be generated from your config file.
 * for your app. This could be expanded in app/tasks for application specific stuff.
 *
 * @package     Fuel
 * @version		1.1
 * @author		Daniel Berry
 * @license     MIT License
 *
 * Usage:
 * php oil r session         = will prompt with menu
 * php oil r session:create  = create the db table.
 * php oil r session:remove  = remove the sessions table
 * php oil r session:clear   = clear the sessions table
 */

class Session {

    // default function if no command is selected. Provided user with menu
    public static function run()
    {
        // Prompt the user with menu options
        $option = \Cli::prompt('What would you like to do?', array('create','remove', 'clear', 'help'));

        switch($option)
        {
            case "create":
                return static::create();
                break;
            case "remove":
                return static::remove();
                break;
            case "clear":
                return static::clear();
                break;
            default:
                return static::help();
                break;
        }
    }

    /*
     * create the sessions table
     * php oil r session:create
     */
    public static function create()
    {
        // load session config
        \Config::load('session', true);

        // make sure session driver is set to db
        if (\Config::get('session.driver') === 'db')
        {

            // create the session table using the table name from the config file
            \DBUtil::create_table(\Config::get('session.db.table'), array(
                'session_id'   => array('constraint' => 40, 'type' => 'varchar'),
                'previous_id'  => array('constraint' => 40, 'type' => 'varchar'),
                'user_agent'   => array('type' => 'text', 'null' => false),
                'ip_hash'      => array('constraint' => 32, 'type' => 'char'),
                'created'      => array('constraint' => 10, 'type' => 'int', 'unsigned' => true),
                'updated'      => array('constraint' => 10, 'type' => 'int', 'unsigned' => true),
                'payload'      => array('type' => 'longtext'),
            ), array('session_id'), false, 'InnoDB', 'utf8');

            // make previous_id a unique_key. speeds up query and prevents duplicate id's
            \DBUtil::create_index(\Config::get('session.db.table'), 'previous_id', 'previous_id', 'unique');

            // return success message.
            return \Cli::color("Success! Your session table has been created!", 'green');
        }
        else
        {
            // driver is not set to db, so inform the user.
            return \Cli::color("Oops, your driver is currently set to ".\Config::get('session.driver').'. Please set your driver type to db before continuing.', 'red');
        }
    }



    /*
     * remove the sessions table
     * php oil r session:remove
     */
    public static function remove()
    {
        // load session config
        \Config::load('session', true);

        // prompt the user to confirm they want to remove the table.
        $iamsure = \Cli::prompt('Are you sure you want to delete the sessions table?', array('y','n'));

        // if they are sure, then let's drop it
        if ($iamsure === 'y')
        {
            \DBUtil::drop_table(\Config::get('session.db.table'));
            return \Cli::color("Session database table deleted.", 'green');
        }

        // if we made it to here, than that means the user said no.
        return \Cli::color("Session database table was not deleted.", 'red');
    }

    /*
     * clear the sessions table
     * php oil r session:clear
     */
    public static function clear()
    {
        // load session config
        \Config::load('session', true);

        // prompt the user to confirm they want to clear the table.
        $iamsure = \Cli::prompt('Are you sure you want to clear the sessions table?', array('y','n'));

        // if they are sure, then let's drop it
        if ($iamsure === 'y')
        {
            \DBUtil::truncate_table(\Config::get('session.db.table'));
            return \Cli::color("Session database table successfully truncated.", 'green');
        }

        // if we made it to here, than that means the user said no.
        return \Cli::color("Session database table was not cleared.", 'red');
    }

    /**
     * Shows basic help instructions for using migrate in oil
     */
    public static function help()
    {
        echo <<<HELP
            Usage:
                php oil refine session

            Description:
                The session task will create the nessecary db tables.

            Examples:
                php oil r session:create
                php oil r session:remove
                php oil r migrate:clear
                php oil r migrate:help

HELP;
    }
}

/* End of file tasks/session.php */
