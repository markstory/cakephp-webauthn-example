<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateUsersTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change(): void {
        $table = $this->table('users')
            ->addColumn('uuid', 'string', ['null' => false])
            ->addColumn('username', 'string', ['null' => false])
            ->addColumn('display_name', 'string', ['null' => true])
            ->addIndex(['username'], ['unique' => true]);
        $table->save();

        $table = $this->table('passkeys')
            ->addColumn('user_id', 'integer', ['null' => false])
            ->addColumn('credential_id', 'string', ['null' => false])
            ->addColumn('payload', 'text')
            ->addForeignKey(['user_id'], 'users');
        $table->save();
    }
}
