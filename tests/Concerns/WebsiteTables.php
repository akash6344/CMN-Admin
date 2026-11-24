<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * In-memory copies of the shared databases Admin reads:
 * the website (CMN-Dashboard: listings, documents, notifications), Operations' reviews (CMN-Emp, connection "ops") and the builder portal (CMN-housing: projects).
 * Laravel runs setUpWebsiteTables() automatically for tests that use this trait.
 */
trait WebsiteTables
{
    protected function setUpWebsiteTables(): void
    {
        config(['database.connections.housing.database' => ':memory:']);
        DB::purge('housing');

        $d = Schema::connection('dashboard');
        $d->create('post_properties', function (Blueprint $t) {
            $t->id();
            $t->integer('user_id')->nullable();
            foreach (['user_name', 'user_mobile', 'user_email', 'user_role', 'posted_as', 'property_id', 'property_status', 'city', 'location', 'property_type', 'property_for',
                'property_title', 'bed', 'bath', 'balcony', 'available_from', 'available_for', 'furnishing', 'built_area', 'built_text', 'total_floors', 'floor_no',
                'e_charges', 'w_charges', 'car_parking', 'bike_parking', 'property_age', 'rent_ag_duration', 'facing', 'price', 'price_text', 'rera', 'rera_id',
                'cover_image', 'post_status', 'show_on_website', 'map'] as $c) {
                $t->string($c)->nullable();
            }
            $t->integer('count')->default(0);
            $t->text('about_property')->nullable();
            $t->text('rejection_reason')->nullable();
            $t->timestamps();
        });
        $d->create('documents', function (Blueprint $t) {
            $t->id();
            $t->integer('user_id');
            $t->integer('post_property_id')->nullable();
            $t->string('title');
            $t->string('status')->default('ready');
            $t->timestamps();
        });
        $d->create('notifications', function (Blueprint $t) {
            $t->id();
            $t->integer('user_id');
            $t->string('title');
            $t->text('body');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        // Operations' review trail: CMN-Emp's DB (connection "ops")
        $o = Schema::connection('ops');
        $o->create('property_reviews', function (Blueprint $t) {
            $t->id();
            $t->string('kind')->default('listing');
            $t->integer('post_property_id')->nullable()->unique();
            $t->integer('project_id')->nullable()->unique();
            $t->string('status')->default('pending');
            $t->integer('score')->nullable();
            $t->text('checks')->nullable();
            $t->text('manual_checks')->nullable();
            $t->text('notes')->nullable();
            $t->text('change_request')->nullable();
            $t->boolean('flagged')->default(false);
            $t->string('flag_reason')->nullable();
            $t->string('reviewer_name')->nullable();
            foreach (['checked_at', 'submitted_to_admin_at', 'admin_query_at', 'decided_at', 'changes_requested_at'] as $c) {
                $t->timestamp($c)->nullable();
            }
            $t->string('decided_by')->nullable();
            $t->text('admin_note')->nullable();
            $t->timestamps();
        });
        $o->create('property_review_events', function (Blueprint $t) {
            $t->id();
            $t->integer('property_review_id');
            $t->string('event');
            $t->string('actor')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
        });

        $h = Schema::connection('housing');
        $h->create('project_details', function (Blueprint $t) {
            $t->id();
            $t->integer('user_id')->nullable();
            foreach (['name', 'builder_name', 'location', 'project_type', 'project_status', 'rera_number', 'land_area', 'reviewed_by'] as $c) {
                $t->string($c)->nullable();
            }
            $t->string('status')->default('draft');
            $t->integer('towers')->nullable();
            $t->integer('total_units')->nullable();
            $t->date('possession_date')->nullable();
            foreach (['description', 'rejection_reason', 'amenities', 'highlights', 'media_preferences'] as $c) {
                $t->text($c)->nullable();
            }
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
        });
        $h->create('unit_configurations', function (Blueprint $t) {
            $t->id();
            $t->integer('project_detail_id');
            $t->string('unit_type');
            $t->decimal('built_up_area', 10, 2);
            $t->decimal('price', 15, 2);
            $t->timestamps();
        });
        $h->create('project_media', function (Blueprint $t) {
            $t->id();
            $t->integer('project_detail_id');
            $t->text('file_url');
            $t->boolean('is_cover')->default(false);
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });
        $h->create('project_documents', function (Blueprint $t) {
            $t->id();
            $t->integer('project_detail_id');
            $t->string('title');
            $t->timestamps();
        });
        $h->create('app_notifications', function (Blueprint $t) {
            $t->id();
            $t->string('audience');
            $t->integer('user_id')->nullable();
            $t->string('company')->nullable();
            $t->string('type');
            $t->string('title');
            $t->string('body')->nullable();
            $t->string('link')->nullable();
            $t->string('tone')->default('info');
            $t->string('dedupe_key')->nullable()->unique();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        $h->create('support_tickets', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->unsignedBigInteger('builder_id')->nullable();
            $t->string('builder_name');
            $t->unsignedBigInteger('project_id')->nullable();
            $t->string('subject');
            $t->string('category');
            $t->string('priority')->default('medium');
            $t->string('status')->default('open');
            $t->string('channel')->default('portal');
            $t->string('raised_by')->default('builder');
            $t->string('assigned_to')->nullable();
            $t->string('opened_by')->nullable();
            foreach (['due_at', 'first_response_at', 'resolved_at', 'builder_seen_at', 'brm_seen_at'] as $c) {
                $t->timestamp($c)->nullable();
            }
            $t->timestamps();
        });
        $h->create('support_ticket_messages', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('support_ticket_id');
            $t->string('type');
            $t->string('author_type');
            $t->string('author')->nullable();
            $t->text('body');
            $t->string('attachment_path')->nullable();
            $t->string('attachment_name')->nullable();
            $t->timestamps();
        });
        $h->create('leads', function (Blueprint $t) {
            $t->id();
            $t->string('lead_code')->unique();
            $t->string('source_platform')->default('dashboard');
            $t->string('name');
            foreach (['email', 'phone', 'city', 'interest_title', 'unit_label', 'builder_name', 'source_detail', 'assigned_to'] as $c) {
                $t->string($c)->nullable();
            }
            $t->integer('project_id')->nullable();
            $t->integer('post_property_id')->nullable();
            $t->string('status')->default('new');
            $t->string('source')->default('favorite');
            $t->text('notes')->nullable();
            $t->text('metadata')->nullable();
            $t->timestamp('last_activity_at')->nullable();
            $t->timestamps();
        });
    }
}
