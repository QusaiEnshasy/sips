<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('skill_test_attempts')) {
            Schema::create('skill_test_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('skill_test_id')->constrained('skill_tests')->cascadeOnDelete();
                $table->string('specialization_code');
                $table->string('specialization_name')->nullable();
                $table->string('status')->default('in_progress');
                $table->json('answers')->nullable();
                $table->dateTime('started_at');
                $table->dateTime('expires_at');
                $table->dateTime('submitted_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'status'], 'skill_attempt_user_status_idx');
                $table->index(['skill_test_id', 'status'], 'skill_attempt_test_status_idx');
            });
        }

        DB::table('skill_tests')->update(['duration_minutes' => 30]);

        foreach ($this->questionBanks() as $code => $questions) {
            $test = DB::table('skill_tests')->where('specialization_code', $code)->first();
            if (! $test) {
                continue;
            }

            $existingCount = DB::table('skill_test_questions')
                ->where('skill_test_id', $test->id)
                ->count();

            $order = $existingCount + 1;
            foreach ($questions as $question) {
                if ($existingCount >= 30) {
                    break;
                }

                $alreadyExists = DB::table('skill_test_questions')
                    ->where('skill_test_id', $test->id)
                    ->where('question', $question['question'])
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                DB::table('skill_test_questions')->insert([
                    'skill_test_id' => $test->id,
                    'question' => $question['question'],
                    'options' => json_encode($question['options']),
                    'correct_answer' => $question['correct_answer'],
                    'order_number' => $order++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $existingCount++;
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_test_attempts');
    }

    private function questionBanks(): array
    {
        return [
            'web_development' => $this->webQuestions(),
            'networking' => $this->networkingQuestions(),
            'cybersecurity' => $this->securityQuestions(),
        ];
    }

    private function webQuestions(): array
    {
        return [
            $this->q('What is the main purpose of HTML?', ['Styling pages', 'Structuring web content', 'Managing databases', 'Encrypting requests'], 1),
            $this->q('Which CSS property changes text color?', ['font-size', 'display', 'color', 'position'], 2),
            $this->q('What does HTTP stand for?', ['HyperText Transfer Protocol', 'High Transfer Text Program', 'Host Transfer Type Protocol', 'Hyperlink Trace Tool'], 0),
            $this->q('Which method is usually used to retrieve data from an API?', ['POST', 'GET', 'DELETE', 'PATCH'], 1),
            $this->q('What is the purpose of a database migration?', ['Compile CSS', 'Track schema changes', 'Send emails', 'Cache images'], 1),
            $this->q('Which command installs JavaScript packages from package.json?', ['npm install', 'php artisan serve', 'composer migrate', 'node clear'], 0),
            $this->q('What is Vue mainly used for?', ['Backend routing', 'Building user interfaces', 'Database backups', 'Server firewalls'], 1),
            $this->q('What is a Laravel controller responsible for?', ['Handling request logic', 'Drawing icons', 'Hosting DNS', 'Compressing images'], 0),
            $this->q('Which file usually stores Laravel environment values?', ['package.json', '.env', 'README.md', 'vite.config.js'], 1),
            $this->q('What does SQL primarily do?', ['Style components', 'Query relational data', 'Render videos', 'Build CSS'], 1),
            $this->q('Which status code means not found?', ['200', '301', '404', '500'], 2),
            $this->q('Which status code means validation error in many Laravel APIs?', ['201', '422', '204', '302'], 1),
            $this->q('What is CSRF protection used for?', ['Prevent forged requests', 'Improve image quality', 'Speed up CSS', 'Compile PHP'], 0),
            $this->q('Which Laravel tool defines web routes?', ['routes/web.php', 'public/index.css', 'storage/logs', 'node_modules'], 0),
            $this->q('What is Vite used for in this project?', ['Frontend build/dev server', 'Database engine', 'Mail server', 'Payment gateway'], 0),
            $this->q('What is JSON commonly used for?', ['Data exchange', 'Font rendering', 'Image editing', 'Network cabling'], 0),
            $this->q('Which HTML tag creates a link?', ['<div>', '<a>', '<span>', '<section>'], 1),
            $this->q('Which CSS layout system is one-dimensional?', ['Flexbox', 'SQL', 'REST', 'Blade'], 0),
            $this->q('Which Laravel ORM model layer is Eloquent?', ['Database ORM', 'CSS framework', 'Queue driver only', 'Icon library'], 0),
            $this->q('What is the purpose of authentication?', ['Verify user identity', 'Minify images', 'Route DNS', 'Format text only'], 0),
            $this->q('Which field type is best for long user text?', ['integer', 'boolean', 'text', 'tinyInteger'], 2),
            $this->q('Which git command shows changed files?', ['git status', 'git erase', 'git serve', 'git cache'], 0),
            $this->q('What does responsive design mean?', ['Works across screen sizes', 'Only desktop support', 'No CSS use', 'Database-only design'], 0),
            $this->q('Which HTTP method is commonly used to update existing data?', ['PUT/PATCH', 'TRACE only', 'CONNECT only', 'HEAD only'], 0),
            $this->q('What is an API endpoint?', ['A URL handling a specific request', 'A CSS class', 'A database password', 'A font file'], 0),
            $this->q('Which template engine does Laravel commonly use?', ['Blade', 'React Native', 'MyISAM', 'Redis'], 0),
            $this->q('What should passwords be stored as?', ['Plain text', 'Hashed values', 'HTML comments', 'CSV only'], 1),
            $this->q('What is pagination used for?', ['Split large lists into pages', 'Encrypt tokens', 'Compile PHP', 'Change DNS'], 0),
            $this->q('Which tool manages PHP dependencies?', ['Composer', 'NPM only', 'Vite only', 'MySQL'], 0),
            $this->q('What is a foreign key used for?', ['Relating database tables', 'Changing font color', 'Opening browsers', 'Serving images'], 0),
        ];
    }

    private function networkingQuestions(): array
    {
        return [
            $this->q('What does DNS translate?', ['Domain names to IP addresses', 'Files to images', 'Passwords to tokens', 'CSS to HTML'], 0),
            $this->q('Which protocol is used for secure web browsing?', ['HTTP', 'HTTPS', 'FTP', 'Telnet'], 1),
            $this->q('Which port is commonly used by HTTPS?', ['21', '22', '80', '443'], 3),
            $this->q('Which port is commonly used by SSH?', ['22', '25', '53', '110'], 0),
            $this->q('What does LAN mean?', ['Local Area Network', 'Large Access Node', 'Linked App Name', 'Logical Admin Network'], 0),
            $this->q('What does WAN mean?', ['Wireless App Node', 'Wide Area Network', 'Web Access Name', 'Wire Admin Network'], 1),
            $this->q('Which device mainly forwards frames using MAC addresses?', ['Switch', 'Router', 'Modem only', 'Printer'], 0),
            $this->q('What is a MAC address?', ['Hardware network identifier', 'Website URL', 'Password type', 'File extension'], 0),
            $this->q('Which protocol checks reachability using ping?', ['ICMP', 'SMTP', 'IMAP', 'POP3'], 0),
            $this->q('Which protocol sends email between mail servers?', ['SMTP', 'DHCP', 'ARP', 'SNMP'], 0),
            $this->q('What does NAT do?', ['Translates private/public addresses', 'Encrypts all files', 'Stores webpages', 'Blocks DNS only'], 0),
            $this->q('Which IP version uses 128-bit addresses?', ['IPv4', 'IPv6', 'ARP', 'TCP'], 1),
            $this->q('Which protocol is connection-oriented?', ['TCP', 'UDP', 'ICMP', 'ARP'], 0),
            $this->q('Which protocol is commonly faster but connectionless?', ['TCP', 'UDP', 'HTTPS', 'SSH'], 1),
            $this->q('What does VLAN provide?', ['Logical network segmentation', 'Printer ink', 'Database indexing', 'Password hashing'], 0),
            $this->q('Which OSI layer is Layer 2?', ['Physical', 'Data Link', 'Network', 'Application'], 1),
            $this->q('Which OSI layer is Layer 4?', ['Transport', 'Session', 'Presentation', 'Application'], 0),
            $this->q('What is bandwidth?', ['Maximum data transfer capacity', 'Password length', 'Screen size', 'Disk space only'], 0),
            $this->q('What is latency?', ['Delay in communication', 'Total storage', 'Cable color', 'User role'], 0),
            $this->q('Which device provides wireless access?', ['Access Point', 'Compiler', 'Spreadsheet', 'Database'], 0),
            $this->q('What does DHCP lease mean?', ['Temporary IP assignment period', 'Permanent cable type', 'DNS password', 'Firewall log'], 0),
            $this->q('Which command can show IP configuration on Windows?', ['ipconfig', 'ls', 'composer', 'npm'], 0),
            $this->q('What is a default gateway?', ['Route out of local network', 'Default website', 'Browser cache', 'Email password'], 0),
            $this->q('What is a subnet?', ['Smaller logical network', 'CSS selector', 'Database table', 'HTML component'], 0),
            $this->q('What does VPN provide?', ['Encrypted tunnel over network', 'Only faster CPU', 'Image resizing', 'Local printing only'], 0),
            $this->q('Which protocol resolves IP to MAC on local networks?', ['ARP', 'DNS', 'SMTP', 'FTP'], 0),
            $this->q('What does packet loss mean?', ['Packets fail to reach destination', 'More disk space', 'New IP assigned', 'CSS not loaded'], 0),
            $this->q('Which cable connector is common for Ethernet?', ['RJ45', 'HDMI', 'USB-C only', 'VGA'], 0),
            $this->q('What does firewall rule usually define?', ['Allowed or blocked traffic', 'Database schema only', 'Font size', 'Screen brightness'], 0),
            $this->q('Which protocol is commonly used to manage network devices?', ['SNMP', 'HTML', 'CSS', 'SQL'], 0),
        ];
    }

    private function securityQuestions(): array
    {
        return [
            $this->q('What does CIA triad stand for?', ['Confidentiality, Integrity, Availability', 'Code, Input, Access', 'Cache, Internet, API', 'Control, Identity, Audit'], 0),
            $this->q('What is encryption?', ['Transform data to unreadable form without key', 'Delete files', 'Speed up networks', 'Open ports'], 0),
            $this->q('What is hashing mainly used for?', ['One-way data fingerprinting', 'Two-way browsing', 'Image compression only', 'Wi-Fi boosting'], 0),
            $this->q('Which is a strong password practice?', ['Long unique password', 'Reuse same password', 'Use 123456', 'Share passwords'], 0),
            $this->q('What is social engineering?', ['Manipulating people to reveal information', 'Building routers', 'Writing CSS', 'Installing RAM'], 0),
            $this->q('What is malware?', ['Malicious software', 'Database migration', 'CSS framework', 'Safe backup'], 0),
            $this->q('What is ransomware?', ['Malware that demands payment', 'Network cable', 'Compiler warning', 'Web font'], 0),
            $this->q('What does XSS target?', ['Injecting scripts into web pages', 'Routing packets', 'Formatting disks', 'Changing IP only'], 0),
            $this->q('What does SQL injection attack?', ['Database queries', 'Monitor brightness', 'Cable speed', 'Keyboard layout'], 0),
            $this->q('What is a vulnerability?', ['Weakness that can be exploited', 'Finished backup', 'Strong password', 'Trusted update'], 0),
            $this->q('What is a patch?', ['Fix for a vulnerability or bug', 'Browser theme', 'Network cable color', 'User avatar'], 0),
            $this->q('What is access control?', ['Managing who can access resources', 'Making icons larger', 'Compressing logs', 'Serving CSS'], 0),
            $this->q('What is authentication?', ['Verifying identity', 'Granting all permissions', 'Deleting logs', 'Changing DNS'], 0),
            $this->q('What is authorization?', ['Checking allowed actions', 'Checking cable quality', 'Encrypting images', 'Writing HTML'], 0),
            $this->q('What is a security audit?', ['Review of controls and risks', 'Frontend animation', 'Printer setup', 'Disk cleanup only'], 0),
            $this->q('What is logging useful for?', ['Tracing events and incidents', 'Making fonts bold', 'Increasing RAM', 'Changing router color'], 0),
            $this->q('What is backup important for?', ['Recovery after loss or attack', 'Only faster browsing', 'Removing encryption', 'Hiding passwords'], 0),
            $this->q('What is zero trust?', ['Never trust by default, verify continuously', 'Trust every internal user', 'Disable passwords', 'Allow all traffic'], 0),
            $this->q('What does DDoS attempt to do?', ['Overwhelm a service with traffic', 'Create backups', 'Improve latency', 'Compress images'], 0),
            $this->q('What is a certificate used for in HTTPS?', ['Verify identity and enable encryption', 'Store images', 'Create CSS', 'Assign DHCP'], 0),
            $this->q('What is endpoint protection?', ['Protecting user devices', 'Only protecting cables', 'Formatting websites', 'Creating routes'], 0),
            $this->q('What should you do with suspicious email links?', ['Avoid clicking and verify source', 'Always click', 'Forward passwords', 'Disable MFA'], 0),
            $this->q('What is privilege escalation?', ['Gaining higher access than allowed', 'Lowering screen brightness', 'Creating a CSS rule', 'Updating DNS'], 0),
            $this->q('What is penetration testing?', ['Authorized security testing', 'Building UI only', 'Writing invoices', 'Replacing routers'], 0),
            $this->q('What is security awareness training?', ['Teaching safe behavior', 'Installing printers', 'Writing migrations', 'Changing logos'], 0),
            $this->q('What is a secret key?', ['Sensitive value used for security operations', 'Public help text', 'CSS class', 'HTML tag'], 0),
            $this->q('Where should API tokens be stored?', ['Secure configuration, not public code', 'In public HTML', 'In comments', 'In screenshots'], 0),
            $this->q('What is rate limiting?', ['Limiting requests to reduce abuse', 'Increasing font size', 'Changing IP class', 'Deleting users'], 0),
            $this->q('What is input validation?', ['Checking user input before processing', 'Ignoring all data', 'Making pages colorful', 'Disabling forms'], 0),
            $this->q('What is incident response?', ['Process for handling security events', 'Creating icons', 'Writing CSS', 'Buying hardware only'], 0),
        ];
    }

    private function q(string $question, array $options, int $correctAnswer): array
    {
        return compact('question', 'options') + ['correct_answer' => $correctAnswer];
    }
};
