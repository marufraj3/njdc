<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 24)->default('editor')->index();
            $table->string('locale', 2)->default('bn');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 64)->default('general')->index();
            $table->string('key', 128)->unique();
            $table->longText('value_bn')->nullable();
            $table->longText('value_en')->nullable();
            $table->string('type', 32)->default('text');
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 32)->default('uploads');
            $table->string('path', 768)->nullable();
            $table->text('remote_url')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('checksum', 64)->nullable()->index();
            $table->string('alt_bn')->nullable();
            $table->string('alt_en')->nullable();
            $table->text('caption_bn')->nullable();
            $table->text('caption_en')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('path', 512)->unique();
            $table->string('slug', 255)->nullable()->index();
            $table->string('template', 64)->default('content');
            $table->string('title_bn');
            $table->string('title_en')->nullable();
            $table->longText('body_bn')->nullable();
            $table->longText('body_en')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('seo_title_bn')->nullable();
            $table->string('seo_title_en')->nullable();
            $table->text('seo_description_bn')->nullable();
            $table->text('seo_description_en')->nullable();
            $table->string('canonical_url', 768)->nullable();
            $table->boolean('is_indexable')->default(true);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('content_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name_bn');
            $table->string('name_en')->nullable();
            $table->string('base_path', 255)->unique();
            $table->json('columns')->nullable();
            $table->json('fields')->nullable();
            $table->string('default_order', 64)->default('published_at_desc');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('content_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_type_id')->constrained()->cascadeOnDelete();
            $table->string('path', 512)->unique();
            $table->string('slug', 255)->nullable()->index();
            $table->string('title_bn');
            $table->string('title_en')->nullable();
            $table->longText('body_bn')->nullable();
            $table->longText('body_en')->nullable();
            $table->text('summary_bn')->nullable();
            $table->text('summary_en')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->dateTime('published_at')->nullable()->index();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->string('seo_title_bn')->nullable();
            $table->string('seo_title_en')->nullable();
            $table->text('seo_description_bn')->nullable();
            $table->text('seo_description_en')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['content_type_id', 'status', 'published_at']);
        });

        Schema::create('media_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_file_id')->constrained()->cascadeOnDelete();
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');
            $table->string('role', 32)->default('attachment');
            $table->string('label_bn')->nullable();
            $table->string('label_en')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['attachable_type', 'attachable_id'], 'media_attachable_index');
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('location', 64)->unique();
            $table->string('name_bn');
            $table->string('name_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('content_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label_bn');
            $table->string('label_en')->nullable();
            $table->text('url')->nullable();
            $table->string('target', 16)->default('_self');
            $table->string('item_type', 24)->default('link');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['menu_id', 'parent_id', 'sort_order']);
        });

        Schema::create('layout_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('layout_sections')->cascadeOnDelete();
            $table->string('area', 32)->index();
            $table->string('key', 64);
            $table->string('type', 64)->index();
            $table->string('title_bn')->nullable();
            $table->string('title_en')->nullable();
            $table->longText('body_bn')->nullable();
            $table->longText('body_en')->nullable();
            $table->text('url')->nullable();
            $table->string('media_url', 1024)->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['area', 'key']);
            $table->index(['area', 'sort_order', 'is_active']);
        });

        Schema::create('layout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('layout_items')->cascadeOnDelete();
            $table->foreignId('content_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('media_file_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label_bn')->nullable();
            $table->string('label_en')->nullable();
            $table->longText('text_bn')->nullable();
            $table->longText('text_en')->nullable();
            $table->text('url')->nullable();
            $table->string('media_url', 1024)->nullable();
            $table->string('icon', 128)->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['layout_section_id', 'sort_order', 'is_active'], 'layout_items_order_index');
        });

        Schema::create('officers', function (Blueprint $table) {
            $table->id();
            $table->string('path', 512)->nullable()->unique();
            $table->string('name_bn');
            $table->string('name_en')->nullable();
            $table->string('designation_bn')->nullable();
            $table->string('designation_en')->nullable();
            $table->string('department_bn')->nullable();
            $table->string('department_en')->nullable();
            $table->string('office_bn')->nullable();
            $table->string('office_en')->nullable();
            $table->string('email')->nullable();
            $table->string('office_phone', 64)->nullable();
            $table->string('mobile', 64)->nullable();
            $table->string('intercom', 64)->nullable();
            $table->string('room', 64)->nullable();
            $table->string('fax', 64)->nullable();
            $table->string('photo_url', 1024)->nullable();
            $table->foreignId('photo_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->longText('bio_bn')->nullable();
            $table->longText('bio_en')->nullable();
            $table->json('duties')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->string('path', 512)->unique();
            $table->string('title_bn');
            $table->string('title_en')->nullable();
            $table->text('description_bn')->nullable();
            $table->text('description_en')->nullable();
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_album_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_file_id')->nullable()->constrained()->nullOnDelete();
            $table->string('image_url', 1024)->nullable();
            $table->string('caption_bn')->nullable();
            $table->string('caption_en')->nullable();
            $table->text('link_url')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source_path', 512)->unique();
            $table->string('destination', 768);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('revisionable_type');
            $table->unsignedBigInteger('revisionable_id');
            $table->json('snapshot');
            $table->string('action', 32);
            $table->boolean('is_working')->default(false)->index();
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['revisionable_type', 'revisionable_id'], 'revisionable_index');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 64)->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id'], 'auditable_index');
        });

        Schema::create('import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 128);
            $table->string('checksum', 64)->nullable();
            $table->string('status', 24)->default('running');
            $table->json('counts')->nullable();
            $table->json('warnings')->nullable();
            $table->longText('error')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_runs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('content_revisions');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('gallery_items');
        Schema::dropIfExists('gallery_albums');
        Schema::dropIfExists('officers');
        Schema::dropIfExists('layout_items');
        Schema::dropIfExists('layout_sections');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('media_attachments');
        Schema::dropIfExists('content_items');
        Schema::dropIfExists('content_types');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('settings');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'locale', 'is_active', 'last_login_at']);
        });
    }
};
