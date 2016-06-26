<?php
namespace Czim\NestedModelUpdater;

use BadMethodCallException;
use Config;
use Czim\NestedModelUpdater\Contracts\NestingConfigInterface;
use Czim\NestedModelUpdater\Data\RelationInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use UnexpectedValueException;

class NestingConfig implements NestingConfigInterface
{

    /**
     * @var null|string
     */
    protected $parentModel;

    /**
     * Sets the parent model FQN to be used if not explicitly provided
     * in other methods
     *
     * @param string $parentModel FQN of the parent model
     * @return $this
     */
    public function setParentModel($parentModel)
    {
        $this->parentModel = $parentModel;
        
        return $this;
    }

    /**
     * Returns a container with information about the nested relation by key
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return RelationInfo
     */
    public function getRelationInfo($key, $parentModel = null)
    {
        if ( ! $this->isKeyNestedRelation($key, $parentModel)) {
            throw new RuntimeException("{$key} is not a nested relation, cannot gather data.");
        }

        $parent = $this->makeParentModel();

        $relationMethod = $this->getRelationMethod($key, $parentModel);
        $relation       = $parent->{$relationMethod}();
        
        return (new RelationInfo())
            ->setRelationMethod($relationMethod)
            ->setRelationClass(get_class($relation))
            ->setModel($this->determineModelForRelation($relation))
            ->setModelPrimaryKey($this->determinePrimaryKeyForRelation($relation))
            ->setSingular($this->isRelationSingular($relation))
            ->setBelongsTo($this->isRelationBelongsTo($relation))
            ->setUpdater($this->getUpdaterClassForKey($key, $parentModel))
            ->setUpdateAllowed($this->isKeyUpdatableNestedRelation($key, $parentModel));
    }

    /**
     * @param string      $key
     * @param null|string $parentModel
     * @return array|boolean
     */
    public function getNestedRelationConfigByKey($key, $parentModel = null)
    {
        $parentModel = $parentModel ?: $this->parentModel;

        return Config::get('nestedmodelupdater.relations.' . $parentModel . '.' . $key, false);
    }

    /**
     * Returns whether a key, for the given model, is a nested relation at all.
     *
     * @param string      $key
     * @param null|string $parentModel      the FQN for the parent model
     * @return boolean
     */
    public function isKeyNestedRelation($key, $parentModel = null)
    {
        $config = $this->getNestedRelationConfigByKey($key, $parentModel);

        return (false !== $config && null !== $config);
    }

    /**
     * Returns whether a key, for the given model, is an updatable nested relation.
     * Updatable relations are relations that may have their contents updated through
     * the nested update operation. This returns false if related models may only be
     * linked, but not modified.
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return boolean
     */
    public function isKeyUpdatableNestedRelation($key, $parentModel = null)
    {
        $config = $this->getNestedRelationConfigByKey($key, $parentModel);
        
        if (true === $config) return true;
        
        if ( ! is_array($config)) return false;
        
        return (bool) Arr::get($config, 'link-only', false);
    }

    /**
     * Returns the name of the method on the parent model for the relation.
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return string|false
     */
    public function getRelationMethod($key, $parentModel = null)
    {
        if ( ! $this->isKeyNestedRelation($key, $parentModel)) {
            return false;
        }

        // todo: retrieve exceptions from config

        // if no exception set, the method is based on the key
        return Str::camel($key);
    }

    /**
     * Returns the model FQN for a given (nested) relation key
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return string|false
     */
    public function getModelClassForKey($key, $parentModel = null)
    {
        if ( ! $this->isKeyNestedRelation($key, $parentModel)) {
            return false;
        }

        // if no exception set, return a model name based on the model namespace
        // and the key name.

        return Config::get('nestedmodelupdater.model_namespace', Str::studly(Str::singular($key)));
    }

    /**
     * Returns the FQN for the ModelUpdater to be used for a specific nested relation key
     *
     * @param string      $key
     * @param null|string $parentModel the FQN for the parent model
     * @return string
     */
    public function getUpdaterClassForKey($key, $parentModel = null)
    {
        // if no exception is set, return the normal updater
        // todo

        return ModelUpdater::class;
    }

    /**
     * Returns a fresh instance of the parent model for the relation.
     *
     * @return Model
     */
    protected function makeParentModel()
    {
        if ( ! $this->parentModel) {
            throw new BadMethodCallException("Could not create parent model, no class name given.");
        }

        $class = $this->parentModel;
        $model = new $class;

        if ( ! ($model instanceof Model)) {
            throw new UnexpectedValueException("Expected Model for parentModel, got {$class} instead.");
        }

        return $model;
    }

    /**
     * Returns the Relation object returned by calling the relation method on a model. 
     * 
     * @param Model  $model
     * @param string $method
     * @return Relation
     */
    protected function makeRelation(Model $model, $method)
    {
        if ( ! method_exists($model, $method)) {
            throw new UnexpectedValueException(
                "Relation method '{$method}' on model " . get_class($model) . ' does not exist'
            );  
        }
        
        $relation = $model->{$method};
        
        if ( ! ($relation instanceof Relation)) {
            throw new UnexpectedValueException(
                "Method '{$method}' on model " . get_class($model) . ' did not return a Relation instance'
            );    
        }
        
        return $relation;
    }

    /**
     * Returns FQN for related model.
     *
     * @param Relation $relation
     * @return string
     */
    protected function determineModelForRelation(Relation $relation)
    {
        return get_class($relation->getRelated());
    }

    /**
     * Returns primary key attribute for related model.
     *
     * @param Relation $relation
     * @return string
     */
    protected function determinePrimaryKeyForRelation(Relation $relation)
    {
        return $relation->getRelated()->getKeyName();
    }

    /**
     * Returns wether relation is of singular type.
     *
     * @param Relation $relation
     * @return bool
     */
    protected function isRelationSingular(Relation $relation)
    {
        return in_array(
            get_class($relation),
            Config::get('nestedmodelupdater.singular-relations', [])
        );
    }

    /**
     * Returns wether relation is of the 'belongs to' type (foreign key
     * stored on the parent).
     *
     * @param Relation $relation
     * @return bool
     */
    protected function isRelationBelongsTo(Relation $relation)
    {
        return in_array(
            get_class($relation),
            Config::get('nestedmodelupdater.belongs-to-relations', [])
        );
    }

}