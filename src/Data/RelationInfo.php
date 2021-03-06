<?php
namespace Czim\NestedModelUpdater\Data;
use Illuminate\Database\Eloquent\Model;

/**
 * Class RelationInfo
 *
 * Container for information about a given relation that the NestingConfig may
 * determine and provide.
 */
class RelationInfo
{

    /**
     * The name of the relation method
     *
     * @var string
     */
    protected $relationMethod;

    /**
     * The FQN of the relation class returned by the relation method
     *
     * @var string
     */
    protected $relationClass;

    /**
     * Whether the relationship is of a One, as opposed to a Many, type
     *
     * @var bool
     */
    protected $singular = true;

    /**
     * Whether the relationship is of the belongsTo type, that is, whether
     * the foreign key for this relation is stored on the main/parent model.
     *
     * @var bool
     */
    protected $belongsTo = false;

    /**
     * An instance of the child model for the relation
     *
     * @var Model|null
     */
    protected $model;

    /**
     * The FQN of the ModelUpdater that should handle update or create process
     *
     * @var string|null
     */
    protected $updater;

    /**
     * Whether it is allowed to update data (and relations) of the nested related
     * records. If this is false, only (dis)connecting relationships should be
     * allowed.
     *
     * @var boolean
     */
    protected $updateAllowed = false;

    /**
     * Whether it is allowed to create nested records for this relation.
     *
     * @var boolean
     */
    protected $createAllowed = false;

    /**
     * Whether missing records in a set of nested data should be detached.
     * If null, default is true for BelongsToMany and false for everything else.
     *
     * @var null|boolean
     */
    protected $detachMissing;

    /**
     * Whether, if detachMissing is true, detached models should be deleted
     * instead of merely dissociated.
     *
     * @var boolean
     */
    protected $deleteDetached = false;

    /**
     * @var null|string     FQN for nested validator that should handle nested validation
     */
    protected $validator;

    /**
     * @var null|string     FQN for the class that provides the rules for the model
     */
    protected $rulesClass;

    /**
     * @var null|string     name of the method that provides the array with rules
     */
    protected $rulesMethod;


    /**
     * @return string
     */
    public function relationMethod()
    {
        return $this->relationMethod;
    }

    /**
     * @param string $relationMethod
     * @return $this
     */
    public function setRelationMethod($relationMethod)
    {
        $this->relationMethod = $relationMethod;

        return $this;
    }

    /**
     * @return string
     */
    public function relationClass()
    {
        return $this->relationClass;
    }

    /**
     * @param string $relationClass
     * @return $this
     */
    public function setRelationClass($relationClass)
    {
        $this->relationClass = $relationClass;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSingular()
    {
        return $this->singular;
    }

    /**
     * @param boolean $singular
     * @return $this
     */
    public function setSingular($singular)
    {
        $this->singular = (bool) $singular;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isBelongsTo()
    {
        return $this->belongsTo;
    }

    /**
     * @param boolean $belongsTo
     * @return $this
     */
    public function setBelongsTo($belongsTo)
    {
        $this->belongsTo = (bool) $belongsTo;

        return $this;
    }
    
    /**
     * @return null|Model
     */
    public function model()
    {
        return $this->model;
    }

    /**
     * @param null|Model $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * @return null|string
     */
    public function updater()
    {
        return $this->updater;
    }

    /**
     * @param null|string $updater
     * @return $this
     */
    public function setUpdater($updater)
    {
        $this->updater = $updater;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isUpdateAllowed()
    {
        return $this->updateAllowed;
    }

    /**
     * @param boolean $updateAllowed
     * @return $this
     */
    public function setUpdateAllowed($updateAllowed)
    {
        $this->updateAllowed = (bool) $updateAllowed;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isCreateAllowed()
    {
        return $this->createAllowed;
    }

    /**
     * @param boolean $createAllowed
     * @return $this
     */
    public function setCreateAllowed($createAllowed)
    {
        $this->createAllowed = (bool) $createAllowed;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDeleteDetached()
    {
        return $this->deleteDetached;
    }

    /**
     * @param boolean $deleteDetached
     * @return $this
     */
    public function setDeleteDetached($deleteDetached)
    {
        $this->deleteDetached = $deleteDetached;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getDetachMissing()
    {
        return $this->detachMissing;
    }

    /**
     * @param bool|null $detachMissing
     * @return $this
     */
    public function setDetachMissing($detachMissing)
    {
        $this->detachMissing = $detachMissing;

        return $this;
    }

    /**
     * @return null|string
     */
    public function validator()
    {
        return $this->validator;
    }

    /**
     * @param string $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * @return null|string
     */
    public function rulesClass()
    {
        return $this->rulesClass;
    }

    /**
     * @param string $rulesClass
     * @return $this
     */
    public function setRulesClass($rulesClass)
    {
        $this->rulesClass = $rulesClass;

        return $this;
    }

    /**
     * @return null|string
     */
    public function rulesMethod()
    {
        return $this->rulesMethod;
    }

    /**
     * @param string $rulesMethod
     * @return $this
     */
    public function setRulesMethod($rulesMethod)
    {
        $this->rulesMethod = $rulesMethod;

        return $this;
    }

}
