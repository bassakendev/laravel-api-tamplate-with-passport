<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'value'];

    /**
     * Get the value as a string or double based on the content.
     *
     * @return mixed
     */
    public function getValueAttribute($value)
    {
        // Si la valeur est numérique, la convertir en double, sinon retourner la chaîne
        return is_numeric($value) ? (float) $value : $value;
    }

    /**
     * Set the value as a string.
     *
     * @param mixed $value
     * @return void
     */
    public function setValueAttribute($value)
    {
        // Convertir la valeur en chaîne de caractères avant de la sauvegarder
        $this->attributes['value'] = (string) $value;
    }
}
