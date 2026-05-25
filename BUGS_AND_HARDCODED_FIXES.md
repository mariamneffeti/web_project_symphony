# 🐛 Bugs and Hardcoded Values - Priority List
**Last Updated:** May 25, 2026

---

## 📊 Summary Overview

| Category | Count | Severity |
|----------|-------|----------|
| **Commented-out authorization checks** | 13 | 🔴 CRITICAL |
| **XSS/HTML injection vulnerabilities** | 20+ | 🔴 CRITICAL |
| **Hardcoded credentials & sensitive data** | 4 | 🔴 CRITICAL |
| **Undefined variable references** | 2 | 🔴 CRITICAL |
| **Unvalidated user input** | 10+ | 🟠 HIGH |
| **File upload validation issues** | 3 | 🟠 HIGH |
| **Missing error handling** | 5+ | 🟠 HIGH |
| **Duplicate code blocks** | 4 | 🟡 MEDIUM |
| **Hardcoded values (non-sensitive)** | 50+ | 🟡 MEDIUM |
| **Inline event handlers** | 20+ | 🟡 MEDIUM |

---

## 🔴 CRITICAL SEVERITY (Fix Immediately)

### 1. **Disabled Authorization Checks** - 13 Instances
**Status:** 🔴 CRITICAL SECURITY RISK  
**Impact:** Entire application is vulnerable to unauthorized access

**Files & Locations:**
- `src/Controller/ClientController.php` - Lines 21, 30, 45, 77, 110 (5 routes)
- `src/Controller/ServicesController.php` - Lines 26, 38, 61, 93, 130, 168 (6 routes)
- `src/Controller/SalesController.php` - Line 20 (1 route)
- `src/Controller/ProductController.php` - Line 20 (1 route)

**Issue:** All `denyAccessUnlessGranted()` calls are commented out. Anyone can access protected routes.

**Fix:**
```php
// Uncomment all instances of:
// $this->denyAccessUnlessGranted('VIEW', $resource);
// $this->denyAccessUnlessGranted('ROLE_ADMIN');
// etc.
```

**Priority:** Do this FIRST before deploying anything.

---

### 2. **XSS Vulnerabilities via innerHTML with Unsanitized Data** - 20+ Instances
**Status:** 🔴 CRITICAL SECURITY RISK  
**Impact:** Users can inject malicious JavaScript that steals data, modifies pages, or compromises other users

**Files & Locations:**
- `public/js/clients.js` - Lines 58, 195, 200 (3+ instances of `.innerHTML = ...${client.email}...`)
- `public/js/clients.js` - Line 201 (innerHTML with client names)
- `public/js/employee dashboard.js` - Lines 95, 271 (2+ innerHTML assignments)
- Templates using inline event handlers with unsanitized output

**Example Issue:**
```javascript
// VULNERABLE:
document.getElementById('clientInfo').innerHTML = `
  <p>Email: ${client.email}</p>  // Could contain <img onerror=alert('XSS')>
`;
```

**Fix - Use textContent instead:**
```javascript
// SAFE:
const p = document.createElement('p');
p.textContent = `Email: ${client.email}`;
element.appendChild(p);
```

**Priority:** Do this SECOND.

---

### 3. **Hardcoded Sensitive Data Exposure** - 4 Critical Instances
**Status:** 🔴 CRITICAL SECURITY RISK  
**Impact:** Passwords and database credentials exposed; attackers can access the database

**Files & Locations:**

#### a) Hardcoded Default Password
- `src/Controller/EmployeeController.php` - Line 77
- Hardcoded: `'Emp@123'` (used as temporary password)
- **EXPOSED IN API RESPONSE** at Line 101: `'temp_password' => $tempPassword`

**Issue:** Everyone getting this response can see the password.

**Fix:**
```php
// BEFORE:
$tempPassword = 'Emp@123';  // Hardcoded!
return $this->json(['temp_password' => $tempPassword]);  // EXPOSED!

// AFTER:
$tempPassword = bin2hex(random_bytes(8));  // Generate random
// Don't send password in response
// Send email to user instead with one-time setup link
```

#### b) Database Credentials in `.env`
- `.env` - Line 48: `DATABASE_URL="mysql://root:@127.0.0.1:3306/web_project?..."`
- Credentials visible in version control

**Fix:**
```bash
# Create .env.local (add to .gitignore):
DATABASE_URL="mysql://root:YOUR_PASSWORD@127.0.0.1:3306/web_project?..."
```

#### c) Empty APP_SECRET
- `.env` - Line 17: `APP_SECRET` is empty
- Will cause CSRF protection to fail

**Fix:**
```bash
# Generate a new secret:
php bin/console secrets:generate-keys
# Then set in .env.local:
APP_SECRET="your_random_secret_here"
```

**Priority:** Do this THIRD (alongside #1).

---

### 4. **Undefined Variable References** - 2 Instances
**Status:** 🔴 CRITICAL BUG  
**Impact:** Code will crash when called with `$slugger is undefined` error

**Files & Locations:**

#### a) NormalUserController.php
- Line 63: `$safeFilename = $slugger->slug(...)`
- `$slugger` is NOT injected in method signature (Lines 27-33)
- Method signature is missing parameter

```php
// CURRENT (BROKEN):
public function store(Request $request): Response {
    // ... code uses $slugger but it's not defined!
    $safeFilename = $slugger->slug($originalFilename);
}

// NEEDS TO BE:
public function store(Request $request, SluggerInterface $slugger): Response {
    $safeFilename = $slugger->slug($originalFilename);
}
```

#### b) ProfileController.php
- Line 51: Same issue - `$slugger` undefined
- Method signature at Lines 24-29 missing `SluggerInterface $slugger` parameter

**Fix:**
```php
use Symfony\Component\String\Slugger\SluggerInterface;

// Add to method signature:
public function methodName(SluggerInterface $slugger): Response {
    // Now $slugger is available
}
```

**Priority:** Fix immediately - these methods will crash when called.

---

## 🟠 HIGH SEVERITY (Fix Soon)

### 5. **Unvalidated User Input** - 10+ Instances
**Status:** 🟠 HIGH RISK - Input validation missing  
**Impact:** Attackers can inject malicious data into your database and bypass business logic

**Files & Locations:**
- `src/Controller/ClientController.php` - Line 47: `json_decode($request->getContent(), true) ?? []` (no type checking)
- `src/Controller/ClientController.php` - Line 84: Same pattern
- `src/Controller/ManagementController.php` - Line 122: `request->request->get('meeting_id')` (no validation)
- `src/Controller/SalesController.php` - Line 134: No null checking after `json_decode`
- `src/Controller/ServicesController.php` - Line 95: No validation after `json_decode`

**Example Issue:**
```php
// VULNERABLE:
$data = json_decode($request->getContent(), true) ?? [];
$client = $data['client_name']; // What if this is an array? An object?

// SAFE:
$data = json_decode($request->getContent(), true) ?? [];
if (!isset($data['client_name']) || !is_string($data['client_name'])) {
    return $this->json(['error' => 'Invalid client name'], 400);
}
$clientName = trim($data['client_name']);
```

**Fix:** Add validation before using user input:
```php
use Symfony\Component\Validator\Validator\ValidatorInterface;
// Or use Symfony forms with built-in validation
```

**Priority:** Fix before next release.

---

### 6. **Insecure File Upload Validation** - 3 Instances
**Status:** 🟠 HIGH RISK - Files not properly validated  
**Impact:** Attackers could upload malicious files (executables, scripts)

**Files & Locations:**
- `src/Controller/EmployeeController.php` - Lines 56-62 (CV upload)
- `src/Controller/EmployeeController.php` - Lines 176-184 (Duplicate CV upload)
- `src/Controller/NormalUserController.php` - Lines 68-81 (Image upload)
- `src/Controller/ProfileController.php` - Lines 51-64 (Image upload)

**Issues:**
1. Only MIME type validation (can be spoofed)
2. `guessExtension()` is unreliable
3. No file content validation
4. No error handling on `mkdir()`

**Example Issue:**
```php
// VULNERABLE:
if ($uploadedFile->getMimeType() === 'application/pdf') {
    // MIME type can be faked! Attacker uploads .exe with pdf MIME type
}

// SAFER:
// 1. Whitelist specific extensions: ['pdf', 'doc', 'docx']
// 2. Use Symfony Validators with FileConstraint
// 3. Check magic bytes/file signatures
// 4. Store files outside web root
// 5. Rename files with random names
```

**Fix:**
```php
use Symfony\Component\Validator\Constraints as Assert;

// In Form or Entity:
#[Assert\File(
    maxSize: '5M',
    mimeTypes: ['application/pdf', 'application/msword'],
    mimeTypesMessage: 'Only PDF and Word documents allowed'
)]
private $cvFile;
```

**Priority:** Fix before accepting user uploads in production.

---

### 7. **Missing Error Handling** - 5+ Instances
**Status:** 🟠 HIGH RISK - Exceptions not caught  
**Impact:** Application crashes with security-revealing stack traces; poor user experience

**Files & Locations:**
- `src/Controller/ProductController.php` - Line 65: `executeQuery()` without try-catch
- `src/Controller/SalesController.php` - Line 70: `executeQuery()` without error handling
- `src/Controller/SalesController.php` - Line 95: No exception handling for database operations
- File operations without checking success (mkdir at Lines 68, 176, etc.)

**Example Issue:**
```php
// VULNERABLE - crashes if query fails:
$results = $queryBuilder->executeQuery();

// SAFE:
try {
    $results = $queryBuilder->executeQuery();
} catch (Exception $e) {
    return $this->json(['error' => 'Failed to load data'], 500);
}
```

**Fix:** Wrap risky operations in try-catch blocks.

**Priority:** Fix before next release.

---

## 🟡 MEDIUM SEVERITY (Fix When Possible)

### 8. **Hardcoded Company IDs** - 3 Instances
**Status:** 🟡 MEDIUM - Functionality issue  
**Impact:** Filters and operations only work for hardcoded company, breaks multi-tenant support

**Files & Locations:**
- `src/Controller/ProductController.php` - Line 27: `getCompanyId()` returns hardcoded `1` with TODO comment
- `src/Controller/SalesController.php` - Line 27: Same hardcoded company ID with TODO
- `src/Controller/ArticleController.php` - Line 28: `setCompanyId(1)` hardcoded

**Issue:**
```php
// WRONG:
public function index(): Response {
    $companyId = 1; // Hardcoded! Only works for company ID 1
    $products = $this->productRepository->findByCompany($companyId);
}

// CORRECT:
public function index(): Response {
    $company = $this->getCompanyContext(); // Get actual company
    $products = $this->productRepository->findByCompany($company->getId());
}
```

**TODO Comments Found:** These are intentional placeholders waiting to be fixed.

**Fix:** Replace with proper company context from authenticated user/session.

**Priority:** Fix to support multi-company functionality.

---

### 9. **Hardcoded File Upload Paths** - 3 Instances
**Status:** 🟡 MEDIUM - Configuration issue  
**Impact:** Paths not configurable; storage location hardcoded

**Files & Locations:**
- `src/Controller/EmployeeController.php` - Lines 66, 176: `/public/uploads/cv`
- `src/Controller/ProfileController.php` - Line 51: `/public/uploads`
- `src/Controller/NormalUserController.php` - Line 68: `/public/uploads`

**Fix:** Extract to configuration:
```yaml
# config/services.yaml
parameters:
  upload.cv_dir: '%kernel.project_dir%/public/uploads/cv'
  upload.image_dir: '%kernel.project_dir%/public/uploads/images'

# In controller:
public function store(
    #[Autowire('%upload.cv_dir%')] string $cvDir
) {
    // Use $cvDir instead of hardcoded path
}
```

**Priority:** Improve when possible.

---

### 10. **Hardcoded Pagination/Limit Values** - 2 Instances
**Status:** 🟡 MEDIUM - Configuration issue  
**Impact:** Max items hardcoded; not configurable

**Files & Locations:**
- `src/Controller/ProductController.php` - Line 50: `min(200, max(1, ...)` hardcoded limit
- `src/Controller/SalesController.php` - Line 44: Same hardcoded limit

**Fix:** Move to configuration:
```yaml
# config/services.yaml
parameters:
  pagination.max_items: 200
  pagination.default_items: 10
```

**Priority:** Improve when possible.

---

### 11. **Duplicate File Upload Logic** - 2 Code Blocks
**Status:** 🟡 MEDIUM - Code maintenance issue  
**Impact:** Difficult to maintain; bugs must be fixed in multiple places

**Files & Locations:**
- `src/Controller/EmployeeController.php` - Lines 66-74 (CV upload)
- `src/Controller/EmployeeController.php` - Lines 176-184 (Identical CV upload - DUPLICATE!)
- `src/Controller/NormalUserController.php` - Lines 68-81 (Image upload)
- `src/Controller/ProfileController.php` - Lines 51-64 (Identical image upload - DUPLICATE!)

**Fix:** Extract to service:
```php
// src/Service/FileUploadService.php
class FileUploadService {
    public function handleCVUpload(UploadedFile $file, string $targetDir): string {
        // Common CV upload logic
    }
    
    public function handleImageUpload(UploadedFile $file, string $targetDir): string {
        // Common image upload logic
    }
}

// Then use in controller:
$filename = $this->fileUploadService->handleCVUpload($file, $cvDir);
```

**Priority:** Refactor when refactoring file uploads.

---

### 12. **Hardcoded Credentials in Default Password** - 1 Instance
**Status:** 🟡 MEDIUM - Weak default  
**Impact:** All temporary passwords are the same predictable password

**Files & Locations:**
- `src/Controller/EmployeeController.php` - Line 77: `'Emp@123'` hardcoded

**Fix:**
```php
// Generate random password:
$tempPassword = bin2hex(random_bytes(6)); // e.g., "a1b2c3d4e5f6"

// Or use Symfony's password generator:
$tempPassword = $this->passwordHasher->hash(bin2hex(random_bytes(8)));

// Send via email instead of in response:
$this->mailer->send($email);
```

**Priority:** Fix with security improvements.

---

### 13. **Hardcoded Colors in JavaScript** - 2 Files
**Status:** 🟡 MEDIUM - Maintenance issue  
**Impact:** Colors duplicated in multiple files; hard to update theme

**Files & Locations:**
- `public/js/sales.js` - Line 55: Colors object with hardcoded hex values
- `public/js/employee dashboard.js` - Line 53: Repeated colors object

**Fix:** Extract to separate config file:
```javascript
// public/js/colors.js
export const COLORS = {
    primary: '#388087',
    dark: '#0d1f1b',
    light: '#F6F6F2',
    // ... other colors
};

// Then import in both files:
import { COLORS } from './colors.js';
```

**Priority:** Improve when updating styling.

---

### 14. **Inline Event Handlers** - 20+ Instances
**Status:** 🟡 MEDIUM - Code quality and maintainability  
**Impact:** Difficult to debug; potential for XSS if mixed with unsanitized data

**Files & Locations:**
- `templates/client/index.html.twig` - Line 71: `onclick="openAddModal()"`
- `templates/client/index.html.twig` - Line 93: `oninput="handleSearch()"`
- `templates/management/index.html.twig` - Line 159: `oninput="filterClientSelect()"`
- **20+ similar instances throughout templates**

**Fix - Use event delegation:**
```javascript
// BEFORE:
// <button onclick="deleteClient(123)">Delete</button>

// AFTER:
// <button class="delete-client" data-id="123">Delete</button>
<script>
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('delete-client')) {
        const id = e.target.dataset.id;
        deleteClient(id);
    }
});
</script>
```

**Priority:** Improve during refactoring.

---

### 15. **Hardcoded Company Name in Templates** - 3 Instances
**Status:** 🟡 MEDIUM - Configuration issue  
**Impact:** Company name hardcoded; doesn't reflect actual company

**Files & Locations:**
- `templates/home/index.html.twig` - Line 369: "© 2026 Entreprisa Inc."
- `templates/base2.html.twig` - Line 189: "© {{ "now"|date("Y") }} Entreprisa"
- `templates/home_company/index.html.twig` - Line 1: "Entreprisa" hardcoded

**Fix:**
```twig
{# Store in configuration or entity #}
© {{ app.request.get('company_name') ?? 'Company Name' }}

{# Or better: #}
© {{ company.name }}
```

**Priority:** Improve when implementing multi-tenant features.

---

### 16. **Hardcoded Database Server Version** - 1 Instance
**Status:** 🟡 MEDIUM - Configuration issue  
**Impact:** Hardcoded MariaDB version; must be updated manually

**Files & Locations:**
- `.env` - Line 48: `serverVersion=mariadb-10.4.32` hardcoded

**Fix:**
```env
# Use environment variable or detect automatically:
DATABASE_URL="mysql://user:pass@host/db"
```

**Priority:** Improve when possible.

---

### 17. **Hardcoded URLs in Templates** - 3 Instances
**Status:** 🟡 MEDIUM - Configuration issue  
**Impact:** External CDN URLs hardcoded; not configurable

**Files & Locations:**
- `templates/client/index.html.twig` - Line 8: googleapis CDN URL
- `templates/client/index.html.twig` - Lines 287-289: jspdf, xlsx CDN URLs

**Fix:**
```twig
{# Move to config #}
<script src="{{ cdn_url('jspdf@2.5.1/dist/jspdf.umd.min.js') }}"></script>
```

**Priority:** Improve when centralizing external dependencies.

---

### 18. **Missing Error Handling on File Operations** - 3 Instances
**Status:** 🟡 MEDIUM - Potential silent failures  
**Impact:** If `mkdir()` fails, error is silent; directory might not exist

**Files & Locations:**
- `src/Controller/EmployeeController.php` - Line 68: `mkdir($cvDir, 0755, true)` no error check
- `src/Controller/EmployeeController.php` - Line 176: Same issue
- `src/Controller/NormalUserController.php` - Line 68: Same issue

**Fix:**
```php
// BEFORE:
mkdir($cvDir, 0755, true);

// AFTER:
if (!is_dir($cvDir) && !mkdir($cvDir, 0755, true)) {
    throw new \RuntimeException("Failed to create upload directory: $cvDir");
}
```

**Priority:** Fix when handling file uploads.

---

### 19. **Missing File Extension Validation** - 3 Instances
**Status:** 🟡 MEDIUM - Security issue  
**Impact:** File extensions not validated; could allow executable uploads

**Files & Locations:**
- `src/Controller/EmployeeController.php` - Lines 66-74: CV upload
- `src/Controller/EmployeeController.php` - Lines 176-184: Duplicate
- `src/Controller/ProfileController.php` - Lines 51-64: Image upload

**Fix:**
```php
$allowedExtensions = ['pdf', 'doc', 'docx'];
$fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    return $this->json(['error' => 'Invalid file type'], 400);
}
```

**Priority:** Fix when improving file upload security.

---

## 📋 ACTION CHECKLIST

### IMMEDIATE (This Week) 🔴
- [ ] **#1** - Uncomment all 13 disabled authorization checks
- [ ] **#2** - Sanitize all 20+ innerHTML assignments in JavaScript
- [ ] **#3** - Remove credentials from .env, move to .env.local
- [ ] **#3** - Generate APP_SECRET
- [ ] **#4** - Fix undefined `$slugger` in NormalUserController.php line 27
- [ ] **#4** - Fix undefined `$slugger` in ProfileController.php line 24
- [ ] Add `.env.local` to `.gitignore`

### SOON (This Month) 🟠
- [ ] **#5** - Add input validation to all 10+ user input instances
- [ ] **#6** - Improve file upload validation (MIME type, extension, magic bytes)
- [ ] **#6** - Add error handling on mkdir operations
- [ ] **#7** - Wrap database operations in try-catch blocks
- [ ] **#18** - Validate file extensions before upload
- [ ] **#19** - Check mkdir() return value

### LATER (Next Month) 🟡
- [ ] **#8** - Replace hardcoded company IDs with actual company context
- [ ] **#9** - Extract hardcoded paths to configuration
- [ ] **#10** - Move pagination limits to configuration
- [ ] **#11** - Extract duplicate file upload logic to service
- [ ] **#12** - Generate random temporary passwords
- [ ] **#13** - Extract colors to shared JavaScript config
- [ ] **#14** - Replace 20+ inline event handlers with event delegation
- [ ] **#15** - Move hardcoded company names to configuration
- [ ] **#16** - Move database version to environment
- [ ] **#17** - Centralize external CDN URLs

---
