<?php

declare(strict_types=1);

namespace Hanafalah\MicroTenant\Models\Tenant;

use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\Tenant as ContractsTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\{
    CentralConnection,
    HasDatabase,
    HasInternalKeys,
    InvalidatesResolverCache,
    TenantRun
};
use Stancl\Tenancy\Database\TenantCollection;
use Hanafalah\LaravelHasProps\Concerns\HasProps;

use Stancl\Tenancy\Events;
use Illuminate\Database\Eloquent\SoftDeletes;
use Hanafalah\LaravelSupport\Models\BaseModel;

class Tenant extends BaseModel implements ContractsTenant, TenantWithDatabase{
    use SoftDeletes, HasProps;
    use CentralConnection,
        HasDatabase,
        // HasDataColumn,
        HasInternalKeys,
        TenantRun,
        InvalidatesResolverCache;


    const FLAG_APP_TENANT     = '0';
    const FLAG_CENTRAL_TENANT = '1';
    const FLAG_TENANT         = '2';

    protected $fillable   = [
        'id','parent_id','name','uuid','reference_id','reference_type',
        'flag','domain_id'
    ];

    protected static $modelsShouldPreventAccessingMissingAttributes = false;

    protected static function booted(): void{
        parent::booted();
        static::creating(function($query){
            if (!isset($query->uuid)) $query->uuid = Str::orderedUuid();
            if (!isset($query->flag)) $query->flag = static::FLAG_TENANT;
        });
    }

    public function getTenantKeyName(): string{
        return 'id';
    }

    public function getConnectionFlagName(): string{
        return match($this->flag){
            static::FLAG_APP_TENANT     => 'central_app',
            static::FLAG_CENTRAL_TENANT => 'central_tenant',
            static::FLAG_TENANT         => 'tenant'
        };
    }

    public function getTenantKey()
    {
        return $this->getAttribute($this->getTenantKeyName());
    }

    //SCOPE SECTION
    public function scopeApp($builder){return $builder->flagIn(static::FLAG_APP_TENANT);}
    public function scopeTenant($builder){return $builder->flagIn(static::FLAG_TENANT);}
    public function scopeCentral($builder){return $builder->flagIn(static::FLAG_CENTRAL_TENANT);}
    //END SCOPE SECTION

    //EIGER SECTION
    public function domain(){return $this->belongsToModel('Domain');}
    public function tenantHasModel(){return $this->hasOneModel('TenantHasModel');}
    public function tenantHasModels(){return $this->hasManyModel('TenantHasModel');}
    public function installationSchema(){return $this->morphOneModel('InstallationSchema','reference');} //ONLY ONE
    public function modelHasFeatures(){return $this->hasManyModel('ModelHasFeature');}
    public function modelHasApp(){return $this->morphOneModel('ModelHasApp','model');}
    public function reference(){return $this->morphTo('reference');}
    //END EIGER SECTION

    public function newCollection(array $models = []): TenantCollection
    {
        return new TenantCollection($models);
    }

    protected $dispatchesEvents = [
        'saving' => Events\SavingTenant::class,
        'saved' => Events\TenantSaved::class,
        'creating' => Events\CreatingTenant::class,
        'created' => Events\TenantCreated::class,
        'updating' => Events\UpdatingTenant::class,
        'updated' => Events\TenantUpdated::class,
        'deleting' => Events\DeletingTenant::class,
        'deleted' => Events\TenantDeleted::class,
    ];
}