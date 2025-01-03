<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateArticlesTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {

    $table = $this->table('articles');
        $table->addColumn('header', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('url', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('timestamp', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['url'], ['unique' => true, 'name' => 'uq_articles_url'])
              ->create();

    }
}
