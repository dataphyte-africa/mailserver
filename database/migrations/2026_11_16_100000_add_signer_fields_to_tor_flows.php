<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('tor_flows', function (Blueprint $t): void { $t->json('content_bard')->nullable()->after('content_html'); $t->json('signer_fields')->nullable()->after('internal_recipients'); }); Schema::table('tor_invitations', fn (Blueprint $t) => $t->json('signer_field_values')->nullable()->after('signer_name')); } public function down(): void { Schema::table('tor_invitations', fn (Blueprint $t) => $t->dropColumn('signer_field_values')); Schema::table('tor_flows', fn (Blueprint $t) => $t->dropColumns(['content_bard','signer_fields'])); } };
