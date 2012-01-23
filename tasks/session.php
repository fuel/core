<?
/**
 * Sessions DB Table Task
 *
 * Run this task to set add/remove/clear the db sessions table
 * for your app. This could be expanded in app/tasks for application specific stuff.
 *
 * @version		1.0
 * @author		Daniel Berry
 * @license     MIT License
 */

namespace Fuel\Tasks;

class Session {

    public function run()
    {
        // Will only accept the options in the array
        $option = \Cli::prompt('What would you like to do?', array('create','remove', 'clear', 'help'));

        switch($option)
        {
            case "create":
                return self::create();
                break;
            case "remove":
                return self::remove();
                break;
            case "clear":
                return self::clear();
                break;
            default:
                return self::help();
                break;
        }
    }

    /*
     * create the sessions table
     * php oil r session:create
     */
    public function create()
    {
        \Config::load('session', true);

        if (\Config::get('session.driver') === 'db')
        {
            \DBUtil::create_table(\Config::get('session.db.table'), array(
                'session_id'   => array('constraint' => 40, 'type' => 'varchar'),
                'previous_id'  => array('constraint' => 40, 'type' => 'varchar'),
                'user_agent'   => array('type' => 'text', 'null' => false),
                'ip_hash'      => array('constraint' => 32, 'type' => 'char'),
                'created'      => array('constraint' => 10, 'type' => 'int', 'unsigned' => true),
                'updated'      => array('constraint' => 10, 'type' => 'int', 'unsigned' => true),
                'payload'      => array('type' => 'longtext'),
            ), array('session_id'), false, 'InnoDB', 'utf8');

            \DBUtil::create_index(\Config::get('session.db.table'), 'previous_id', 'previous_id', 'unique');

            return \Cli::color("Success! Your session table has been created!", 'green');
        }
        else
        {
            return \Cli::color("Oops, your driver is currently set to ".\Config::get('session.driver').'. Please set your driver type to db before continuing.', 'red');
        }
    }



    /*
     * remove the sessions table
     * php oil r session:remove
     */
    public function remove()
    {
        \Config::load('session', true);

        // Will only accept the options in the array
        $iamsure = \Cli::prompt('Are you sure you want to delete the sessions table?', array('y','n'));

        if ($iamsure === 'y')
        {
            \DBUtil::drop_table(\Config::get('session.db.table'));
            return \Cli::color("Session database table deleted.", 'green');
        }

        return \Cli::color("Session database table was not deleted.", 'red');
    }

    /*
     * truncate the sessions table
     * php oil r session:clear
     */
    public function clear()
    {
        \Config::load('session', true);

        // Will only accept the options in the array
        $iamsure = \Cli::prompt('Are you sure you want to clear the sessions table?', array('y','n'));

        if ($iamsure === 'y')
        {
            \DBUtil::truncate_table(\Config::get('session.db.table'));
            return \Cli::color("Session database table successfully truncated.", 'green');
        }

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

/* End of file tasks/robots.php */