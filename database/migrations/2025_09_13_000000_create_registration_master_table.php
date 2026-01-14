<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registration_master', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id()->comment('Registration ID');

            $table->unsignedBigInteger('company_id')->comment('FK companies.id (tenant)');
            $table->unsignedBigInteger('user_id')->comment('FK users.id (1:1 user ↔ registration)');

            // Registration key issued from company_reg_keys (composite with company_id must be unique)
            // NOTE: column name kept as lower_snake but MySQL is case-insensitive, so old Reg_Key data is compatible.
            $table->string('reg_key', 50)->nullable()->comment('Registration key issued for this user under the company');

            // Set immediately when user picks one of 3 options; controller also sets role_type_id
            $table->enum('registration_type', ['client','company_officer','professional'])
                  ->comment('Set at start from UI selection');

            // MUST be set at start (mapped from registration_type)
            $table->unsignedBigInteger('role_type_id')
                  ->comment('FK role_types.id (client→Client, company_officer→Business Officer, professional→Professional)');

            $table->string('full_name', 150);
            $table->enum('gender', ['male','female','other']);
            $table->date('date_of_birth')->nullable();

            $table->string('phone', 30);
            $table->string('email', 150)->nullable();

            // Officer / Professional specific fields
            $table->string('Employee_ID', 50)->nullable()->comment('Employee ID (unique per company for officers/professionals)');

            // NID & image paths – required by CompanyOfficerController and other modules
            // Use string because real NID can exceed BIGINT precision; numeric validation is enforced in app + DB CHECK.
            $table->string('NID', 25)->nullable()->comment('National ID number as numeric string');

            // Final stored image paths (relative to public assets, as per image-handling pattern)
            $table->string('Photo', 255)->nullable()->comment('Profile photo path');
            $table->string('NID_Photo_Front_Page', 255)->nullable()->comment('NID front image path');
            $table->string('NID_Photo_Back_Page', 255)->nullable()->comment('NID back image path');

            // Geo
            $table->unsignedBigInteger('division_id');
            $table->unsignedBigInteger('district_id');
            $table->unsignedBigInteger('upazila_id');
 
            $table->enum('person_type', ['J','B','H','S','P','O'])
                  ->comment('J=Service Holder, B=Business Man, H=House Wife, S=Student, P=Professional, O=Other');

            // FK to professions table (kept as `Profession` to stay compatible with existing controller/DB dump)
            $table->unsignedBigInteger('Profession')->nullable()->comment('FK professions.id');

            $table->string('present_address', 255)->nullable();
            $table->string('notes', 255)->nullable();

            // Approval lifecycle
            $table->enum('approval_status', ['pending','approved','declined'])
                  ->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable()->comment('users.id who approved/declined');
            $table->timestamp('approved_at')->nullable();

            // Active flag for registration record (Edit Registration visibility relies on this)
            $table->tinyInteger('status')->default(1)
                  ->comment('0 -> Pending, 1 -> Approved/Active, 2-> Declined , 3-> Inactive');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Uniques
            $table->unique(['user_id'], 'uq_reg_user');
            $table->unique(['company_id', 'phone'], 'uq_reg_company_phone');
            $table->unique(['company_id', 'email'], 'uq_reg_company_email');
            $table->unique(['company_id', 'reg_key'], 'uq_reg_company_regkey');

            // For officer/registration constraints
            $table->unique(['company_id', 'Employee_ID'], 'uq_reg_company_employee');
            $table->unique(['company_id', 'NID'], 'uq_reg_company_nid');

            // Indexing
            $table->index('company_id', 'idx_reg_company_id');
            $table->index('user_id', 'idx_reg_user_id');
            $table->index('role_type_id', 'idx_reg_role_type_id');
            $table->index('registration_type', 'idx_reg_reg_type');
            $table->index('division_id', 'idx_reg_division_id');
            $table->index('district_id', 'idx_reg_district_id');
            $table->index('upazila_id', 'idx_reg_upazila_id');
            $table->index('person_type', 'idx_reg_person_type');
            $table->index('Profession', 'idx_reg_profession');
            $table->index('approval_status', 'idx_reg_approval_status');
            $table->index('status', 'idx_reg_status');
            $table->index('deleted_at', 'idx_reg_deleted_at');
            $table->index('created_by', 'idx_reg_created_by');
            $table->index('updated_by', 'idx_reg_updated_by');
            $table->index('approved_by', 'idx_reg_approved_by');
            $table->index('NID', 'idx_reg_nid');

            // FKs
            $table->foreign('company_id', 'fk_reg_company')
                  ->references('id')->on('companies')->onDelete('cascade');

            $table->foreign('user_id', 'fk_reg_user')
                  ->references('id')->on('users')->onDelete('cascade');

            $table->foreign('role_type_id', 'fk_reg_role_type')
                  ->references('id')->on('role_types')->onDelete('restrict');

            $table->foreign('division_id', 'fk_reg_division')
                  ->references('id')->on('divisions')->onDelete('restrict');

            $table->foreign('district_id', 'fk_reg_district')
                  ->references('id')->on('districts')->onDelete('restrict');

            $table->foreign('upazila_id', 'fk_reg_upazila')
                  ->references('id')->on('upazilas')->onDelete('restrict');


            $table->foreign('approved_by', 'fk_reg_approved_by')
                  ->references('id')->on('users')->onDelete('set null');

            // IMPORTANT:
            // We are NOT adding fk_reg_profession or fk_reg_company_regkey here because
            // 2025_09_26_000013_alter_registration_master_add_officer_fields_v2
            // is already responsible for those FKs (and it checks existence).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_master');
    }
};
