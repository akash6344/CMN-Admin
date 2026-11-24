<?php

namespace App\Models;

use App\Models\Dashboard\ListingDocument;
use App\Models\Dashboard\PropertyReview;
use App\Support\ListingStatus;
use Database\Factories\PostPropertyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A listing on the website, posted by an owner or agent (CMN-Dashboard post_properties).
 * Operations (CMN-Emp) reviews it first; Admin approves it from the Properties section.
 */
class PostProperty extends Model
{
    /** @use HasFactory<PostPropertyFactory> */
    use HasFactory;

    protected $connection = 'dashboard';

    protected $table = 'post_properties';

    /**
     * Specifications shown on the property detail.
     *
     * @var array<string, string>
     */
    public const SPEC_LABELS = [
        'property_for' => 'Listed For',
        'bed' => 'Bedrooms',
        'bath' => 'Bathrooms',
        'balcony' => 'Balconies',
        'built_area' => 'Built-up Area',
        'furnishing' => 'Furnishing',
        'floor_no' => 'Floor No',
        'total_floors' => 'Total Floors',
        'facing' => 'Facing',
        'property_age' => 'Property Age',
        'available_from' => 'Available From',
        'available_for' => 'Available For',
        'car_parking' => 'Car Parking',
        'bike_parking' => 'Bike Parking',
        'e_charges' => 'Electricity Charges',
        'w_charges' => 'Water Charges',
        'rent_ag_duration' => 'Rent Agreement',
    ];

    /** Price units the website stores next to the number (price_text). */
    private const UNITS = ['cr' => 1e7, 'crore' => 1e7, 'lac' => 1e5, 'lakh' => 1e5, 'l' => 1e5, 'k' => 1e3];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id', 'user_name', 'user_email', 'user_mobile', 'user_role', 'posted_as', 'property_id',
        'property_type', 'property_for', 'property_status', 'property_title', 'city', 'location',
        'price', 'price_text', 'post_status', 'form_status', 'show_on_website', 'rejection_reason', 'rejection_comment', 'count', 'cover_image',
        'bed', 'bath', 'balcony', 'available_from', 'available_for', 'furnishing', 'built_area', 'built_text',
        'total_floors', 'floor_no', 'facing', 'e_charges', 'w_charges', 'car_parking', 'bike_parking',
        'property_age', 'rent_ag_duration', 'about_property', 'rera',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'count' => 'integer',
        ];
    }

    /** Live website: documents.property_id. Local CMN-Dashboard copy: documents.post_property_id. */
    public function documents(): HasMany
    {
        return $this->hasMany(ListingDocument::class, ListingDocument::listingKey());
    }

    /** Operations' review — in the Operations DB (connection "ops"): never use it in whereHas / joins. */
    public function review(): HasOne
    {
        return $this->hasOne(PropertyReview::class, 'post_property_id');
    }

    /** Short code used across Operations and Admin (P214). */
    public function code(): string
    {
        return 'P'.str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }

    public function ownerName(): string
    {
        return (string) ($this->user_name ?: '—');
    }

    public function submitterRole(): string
    {
        return ListingStatus::isAgent($this) ? 'Agent' : 'Owner';
    }

    /** Website status, before any Operations / Admin stage is applied. */
    public function displayStatus(): string
    {
        return match (ListingStatus::of($this)) {
            'live' => strcasecmp((string) $this->show_on_website, 'show') === 0 ? 'Live' : 'Approved · Hidden',
            'rejected' => 'Rejected',
            'paused' => 'Paused',
            'deleted' => 'Deleted',
            'draft' => 'Draft',
            default => 'Pending',
        };
    }

    public function statusBadgeClass(): string
    {
        return match (ListingStatus::of($this)) {
            'live' => 'badge-approved',
            'rejected', 'deleted' => 'badge-rejected',
            'paused', 'draft' => 'badge-draft',
            default => 'badge-pending',
        };
    }

    /** Price in rupees — the website stores "75" + "Lac", "1.15" + "Cr" and so on. */
    public function priceValue(): ?float
    {
        $number = (float) preg_replace('/[^\d.]/', '', (string) $this->price);
        if ($number <= 0) {
            return null;
        }
        $unit = strtolower(trim((string) $this->price_text, " .\t"));

        return $number * (self::UNITS[$unit] ?? 1);
    }

    public function formattedPrice(): string
    {
        $value = (float) $this->priceValue();

        return self::money($value).($value && str_contains(strtolower((string) $this->property_for), 'rent') ? '/month' : '');
    }

    public static function money(float $value): string
    {
        return match (true) {
            ! $value => '—',
            $value >= 1e7 => '₹'.rtrim(rtrim(number_format($value / 1e7, 2), '0'), '.').' Cr',
            $value >= 1e5 => '₹'.rtrim(rtrim(number_format($value / 1e5, 2), '0'), '.').' L',
            default => '₹'.number_format($value),
        };
    }

    public function priceLabel(): string
    {
        return $this->formattedPrice();
    }

    public function coverUrl(): ?string
    {
        $image = trim((string) $this->cover_image);
        if ($image === '') {
            return null;
        }
        if (str_starts_with($image, 'http')) {
            return $image;
        }
        $base = rtrim((string) config('services.website.property_image_base'), '/');

        return $base !== '' ? $base.'/'.ltrim($image, '/') : null;
    }

    public function primaryImageUrl(): ?string
    {
        return $this->coverUrl();
    }

    public function place(): string
    {
        return collect([$this->location, $this->city])->filter()->implode(', ');
    }

    /**
     * @return list<array{key: string, label: string, value: string}>
     */
    public function specsForDisplay(): array
    {
        $specs = [];
        foreach (self::SPEC_LABELS as $key => $label) {
            $raw = $this->getAttribute($key);
            $value = is_string($raw) || is_numeric($raw) ? trim((string) $raw) : '';
            if ($key === 'built_area' && $value !== '') {
                $value .= ' '.($this->built_text ?: 'sq.ft');
            }
            $specs[] = ['key' => $key, 'label' => $label, 'value' => $value !== '' ? $value : '—'];
        }

        return $specs;
    }
}
