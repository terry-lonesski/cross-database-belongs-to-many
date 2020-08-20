<?php


namespace App\Eloquent\Traits;

use App\Eloquent\Relations\CrossDatabaseBelongsToMany;
use Illuminate\Database\ConnectionInterface;

trait HasCrossDatabaseBelongsToMany
{
    /**
     * Define a many-to-many relationship.
     *
     * @param string $related
     * @param null $table
     * @param ConnectionInterface|null $connection
     * @param null $foreignKey
     * @param null $relatedKey
     * @param null $relation
     *
     * @return CrossDatabaseBelongsToMany
     */
    public function belongsToManyInDifferentConnections(
        $related,
        $table = null,
        $foreignKey = null,
        $relatedKey = null,
        ConnectionInterface $connection = null,
        $relation = null
    )
    {
        if (is_null($relation)) {
            $relation = $this->guessBelongsToManyRelation();
        }

        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $relatedKey = $relatedKey ?: $instance->getForeignKey();

        if (is_null($table)) {
            $table = $this->joiningTable($related);
        }

        if (is_null($connection)) {
            $connection = $this->getConnection();
        }

        return new CrossDatabaseBelongsToMany(
            $instance->newQuery(), $this, $connection, $table, $foreignKey, $relatedKey, $relation
        );
    }
}
