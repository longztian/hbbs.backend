import sys
import os
import mysql.connector
import inflection

def getTables(cursor):
    cursor.execute('SHOW TABLES')
    return [r[0] for r in cursor]


def getFields(cursor, table):
    cursor.execute('DESCRIBE ' + table)
    return [r[0] for r in cursor]


def php(cls, table, fields):
    return """<?php declare(strict_types=1);

namespace site\dbobject;

use lzx\db\DB;
use lzx\db\DBObject;

class """ + cls + """ extends DBObject
{
""" + '\n'.join(['    public $' + inflection.camelize(f, False) + ';' for f in fields]) + """

    public function __construct($id = null, string $properties = '')
    {
        parent::__construct(DB::getInstance(), '""" + table + """', $id, $properties);
    }
}
"""


if __name__ == '__main__':
    database = sys.argv[1]
    basedir = sys.argv[2]
    cnx = mysql.connector.connect(option_files=os.path.expanduser('~/.my.cnf'), database=database)
    cursor = cnx.cursor()

    for table in getTables(cursor):
        cls = inflection.camelize(inflection.singularize(table))
        file = basedir + cls + '.php'

        if os.path.isfile(file):
            print('skip ' + file + ' (file exists)')
        else:
            print('generating ' + file + ' for ' + table)

        with open(file, 'w') as f:
            f.write(php(cls, table, getFields(cursor, table)))
