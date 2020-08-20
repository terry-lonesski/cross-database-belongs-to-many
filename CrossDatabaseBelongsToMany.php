<?php


namespace App\Eloquent\Relations;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class BelongsToManyInDifferentConnections
 * @package App\Eloquent\Relations
 * @property Model $parent
 */
class CrossDatabaseBelongsToMany extends BelongsToMany
{
    public $connection;

    public function __construct(
        Builder $query,
        Model $parent,
        ConnectionInterface $connection,
        $table,
        $foreignKey,
        $relatedKey,
        $relationName = null
    )
    {
        $this->connection = $connection;

        parent::__construct($query, $parent, $table, $foreignKey, $relatedKey, $parent->getKeyName(), $relatedKey, $relationName);
    }

    /**
     * Set the where clause for the relation query.
     *
     * @return $this
     */
    protected function addWhereConstraints()
    {
        return $this;
    }

    protected function performJoin($query = null)
    {
        $query = $query ?: $this->query;

        $baseTable = $this->related->getTable();

        $key = $baseTable . '.' . $this->related->getKeyName();

        $idsQuery = $this->parent->getConnection()
            ->table($this->table)
            ->distinct()
            ->select($this->relatedKey);

        if (!is_null($this->parent->id)) {
            $idsQuery->where($this->parent->getForeignKey(), $this->parent->id);
        }

        $ids = $idsQuery->pluck($this->relatedKey)->all();

        $query->whereIn($key, $ids);

        return $query;
    }

    protected function aliasedPivotColumns()
    {
        $defaults = [$this->foreignPivotKey, $this->relatedPivotKey];

        return collect(array_merge($defaults, $this->pivotColumns))
            ->map(function ($column) {
                if (!in_array($column, ['created_at', 'updated_at'])) {
                    return $column;
                }

                return $this->related->getTable() . '.' . $column . ' as pivot_' . $column;
            })
            ->map(function ($column) {
                if (!in_array($column, [$this->relatedKey])) {
                    return $column;
                }

                return $this->related->getTable() . '.' . $this->related->getKeyName() . ' as pivot_' . $column;
            })
            ->map(function ($column) {
                if ($column !== $this->parent->getForeignKey()) {
                    return $column;
                }

                return $this->parent->getConnection()->raw($this->parent->id . ' as pivot_' . $column);
            })
            ->unique()
            ->all();
    }

    /**
     * Get a new plain query builder for the pivot table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newPivotStatement()
    {
        return $this->connection->table($this->table);
    }
}
