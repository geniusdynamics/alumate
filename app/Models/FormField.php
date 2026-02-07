<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_id',
        'field_type',
        'field_name',
        'field_label',
        'field_placeholder',
        'field_options',
        'validation_rules',
        'conditional_logic',
        'order_index',
        'is_required',
        'is_visible',
        'crm_field_mapping',
    ];

    protected $casts = [
        'field_options' => 'array',
        'validation_rules' => 'array',
        'conditional_logic' => 'array',
        'is_required' => 'boolean',
        'is_visible' => 'boolean',
        'crm_field_mapping' => 'array',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(FormBuilder::class, 'form_id');
    }

    public function getFieldTypeOptions(): array
    {
        return [
            'text' => 'Text Input',
            'email' => 'Email Input',
            'phone' => 'Phone Number',
            'textarea' => 'Text Area',
            'select' => 'Dropdown Select',
            'radio' => 'Radio Buttons',
            'checkbox' => 'Checkboxes',
            'file' => 'File Upload',
            'date' => 'Date Picker',
            'number' => 'Number Input',
            'url' => 'URL Input',
            'hidden' => 'Hidden Field',
        ];
    }
}
