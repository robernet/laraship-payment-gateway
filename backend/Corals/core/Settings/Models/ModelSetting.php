<?php

namespace Corals\Settings\Models;

use Corals\Foundation\Models\BaseModel;
use Corals\Foundation\Transformers\PresentableTrait;

class ModelSetting extends BaseModel
{
    use PresentableTrait;

    /**
     *  Model configuration.
     * @var string
     */
    public $config = 'settings.models.setting';

    protected $guarded = ['id'];

    protected $casts = [
        'properties' => 'json'
    ];

    protected $table = "model_settings";


    public function getFilePath()
    {
        return config('settings.upload_path') . '/' . $this->attributes['value'];
    }


}
