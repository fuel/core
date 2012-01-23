<?php
/**
 * Simple task to create the session db table.
 * Date: 1/23/12
 * Time: 11:22 AM
 */

namespace Fuel\Tasks;

class Session {

    /*
     * create the sessions table
     * php oil r session
     */
    public function run()
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
     * php oil r session:down
     */
    public function down()
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
     * php oil r session:truncate
     */
    public function truncate()
    {
        \Config::load('session', true);

        // Will only accept the options in the array
        $iamsure = \Cli::prompt('Are you sure you want to truncate the sessions table?', array('y','n'));

        if ($iamsure === 'y')
        {
            \DBUtil::truncate_table(\Config::get('session.db.table'));
            return \Cli::color("Session database table successfully truncated.", 'green');
        }

        return \Cli::color("Session database table was not truncated.", 'red');
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
                php oil r session
                php oil r session:down
                php oil r migrate:truncate
                php oil r migrate:help
                
HELP;
    }
}

/* End of file tasks/robots.php */