# 📚 Architecture & Concepts Guide - Symfony Project Validation
**Created:** May 25, 2026  
**Project:** Web Project - Symfony Framework  

---

## 📖 Table of Contents
1. [Project Overview](#project-overview)
2. [Core Architecture](#core-architecture)
3. [Entity-Repository-Controller Flow](#entity-repository-controller-flow)
4. [Detailed Concept Explanations](#detailed-concept-explanations)
5. [Security Architecture](#security-architecture)
6. [Common Patterns Used](#common-patterns-used)
7. [Project Structure](#project-structure)
8. [Likely Interview Questions](#likely-interview-questions)
9. [Example Workflow](#example-workflow)

---

## 🎯 Project Overview

### What Does This Project Do?
This is a **multi-company, multi-role business management system** built with Symfony. It allows companies to manage:
- **Employees** - Store employee information, profiles, CVs
- **Clients** - Manage client contacts and relationships
- **Products/Stock** - Inventory management
- **Sales** - Track sales transactions
- **Articles** - Content management
- **Finance** - Financial tracking
- **HR/Recruitment** - Hiring and recruitment pipeline
- **Services** - Service offerings

### Key Characteristics
- **Multi-tenant:** Different companies/organizations in one system
- **Role-based access:** Different user types (admin, employee, client, normal user)
- **Web-based UI:** HTML/Twig templates + JavaScript
- **Database-driven:** Stores data in MySQL/MariaDB
- **REST API:** Provides endpoints for operations

### Tech Stack
- **Framework:** Symfony 7.x (PHP web framework)
- **Database:** MySQL/MariaDB with Doctrine ORM
- **Frontend:** Twig templates, HTML, CSS, JavaScript
- **Authentication:** Symfony Security component
- **Database Migrations:** Doctrine Migrations

---

## 🏗️ Core Architecture

### Layered Architecture (MVC Pattern)

```
┌─────────────────────────────────────────────────────────────┐
│                    User Interface (Browser)                   │
│              HTML/Twig Templates + JavaScript                │
└────────────────────────┬────────────────────────────────────┘
                         │
                    HTTP Request
                         │
┌────────────────────────▼────────────────────────────────────┐
│                      Routing                                  │
│           (Matches URL to Controller Action)                 │
└────────────────────────┬────────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────────┐
│                  CONTROLLER LAYER                             │
│    ✓ Handles HTTP requests                                   │
│    ✓ Calls repository methods                                │
│    ✓ Calls services                                          │
│    ✓ Returns response (JSON or HTML)                         │
└────────────────────────┬────────────────────────────────────┘
                         │
        ┌────────────────┼────────────────┐
        │                │                │
┌───────▼────────┐ ┌────▼─────────┐ ┌───▼──────────┐
│  REPOSITORY    │ │   ENTITY     │ │    SERVICE   │
│  LAYER         │ │   OBJECTS    │ │    LAYER     │
│                │ │              │ │              │
│ ✓ Queries DB   │ │ ✓ Data       │ │ ✓ Business   │
│ ✓ Returns data │ │ ✓ Properties │ │   logic      │
└────────┬───────┘ │ ✓ Methods    │ │ ✓ Complex    │
         │         └──────────────┘ │   operations │
         │                          └──────────────┘
         │
┌────────▼──────────────────────────────────────┐
│         DATABASE (MySQL/MariaDB)               │
│              Tables/Records                    │
└───────────────────────────────────────────────┘
```

---

## 🔄 Entity-Repository-Controller Flow

### What is an ENTITY?

**Definition:** A PHP class that represents a **table in the database**. Each property represents a column.

**Example:**
```php
// src/Entity/Employee.php
#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'employee')]
class Employee {
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column]
    private ?int $companyId = null;

    // Getters and setters...
    public function getId(): ?int { return $this->id; }
    public function setFirstName(string $name): self { 
        $this->firstName = $name;
        return $this;
    }
}
```

**Entity = OOP representation of database row**
- Each instance = one row in the database
- Properties = columns
- Doctrine ORM automatically converts between PHP objects and SQL

---

### What is a REPOSITORY?

**Definition:** A class responsible for **retrieving data from the database**. It provides query methods.

**Example:**
```php
// src/Repository/EmployeeRepository.php
class EmployeeRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Employee::class);
    }

    // Find employee by ID
    public function findById(int $id): ?Employee {
        return $this->find($id);
    }

    // Find all employees for a company
    public function findByCompany(int $companyId): array {
        return $this->createQueryBuilder('e')
            ->where('e.companyId = :company')
            ->setParameter('company', $companyId)
            ->orderBy('e.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Advanced query example
    public function findActiveEmployees(): array {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }
}
```

**Repository = Data access layer**
- Encapsulates database queries
- Returns Entity objects
- Follows Single Responsibility Principle (only for queries)
- Keeps business logic out of SQL

---

### What is a CONTROLLER?

**Definition:** A PHP class that **handles HTTP requests and returns responses**. It orchestrates the flow.

**Example:**
```php
// src/Controller/EmployeeController.php
#[Route('/employee', name: 'employee_')]
final class EmployeeController extends AbstractController {
    
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private EntityManagerInterface $entityManager
    ) {}

    // Display all employees
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response {
        // Use repository to get data
        $employees = $this->employeeRepository->findByCompany(1);
        
        // Pass to template
        return $this->render('employee/index.html.twig', [
            'employees' => $employees
        ]);
    }

    // Create new employee
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response {
        $data = json_decode($request->getContent(), true);
        
        // Create new entity
        $employee = new Employee();
        $employee->setFirstName($data['firstName']);
        $employee->setLastName($data['lastName']);
        $employee->setCompanyId($data['companyId']);
        
        // Save to database
        $this->entityManager->persist($employee);
        $this->entityManager->flush();
        
        return $this->json(['success' => true, 'id' => $employee->getId()]);
    }
}
```

**Controller = Request/Response handler**
- Receives HTTP requests
- Calls repository to fetch data
- Performs business logic
- Returns response (HTML or JSON)

---

### Complete Flow Example: "Get all employees for company 1"

```
1. User clicks link or makes request
   ↓
   GET /employee

2. Routing matches route to controller action
   ↓
   EmployeeController::index()

3. Controller executes
   ↓
   $employees = $this->employeeRepository->findByCompany(1);

4. Repository queries database
   ↓
   SELECT * FROM employee WHERE companyId = 1

5. Doctrine converts SQL rows to Entity objects
   ↓
   [Employee(id=1, firstName='John', ...), Employee(id=2, ...)]

6. Controller passes entities to template
   ↓
   return $this->render('employee/index.html.twig', 
      ['employees' => $employees]);

7. Twig template renders HTML
   ↓
   {% for employee in employees %}
       <tr>
           <td>{{ employee.firstName }}</td>
           <td>{{ employee.lastName }}</td>
       </tr>
   {% endfor %}

8. HTML sent to browser
   ↓
   User sees employee list in table
```

---

## 📚 Detailed Concept Explanations

### 1. ENTITIES - Object Representation of Database

**What they are:**
- PHP classes decorated with Doctrine ORM attributes
- Map to database tables automatically
- Follow object-oriented principles

**Key Components:**

```php
#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\Table(name: 'employee')]
class Employee {
    // PRIMARY KEY - unique identifier
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // STANDARD COLUMN - maps to 'first_name' column
    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    // NULLABLE COLUMN - allows NULL values
    #[ORM\Column(nullable: true)]
    private ?string $middleName = null;

    // RELATIONSHIP - foreign key to another entity
    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id')]
    private ?Company $company = null;

    // Methods (getters/setters)
    public function getId(): ?int { return $this->id; }
    public function setFirstName(string $name): self {
        $this->firstName = $name;
        return $this;
    }
}
```

**Benefits:**
- Type-safe (no SQL strings in code)
- Automatic SQL generation
- Relationships managed automatically
- Easy to validate
- Version control friendly (no schema drift)

---

### 2. REPOSITORIES - Data Access Objects

**Purpose:** Centralize all database queries for one entity.

**Responsibilities:**
- ✅ Query the database
- ✅ Return Entity objects
- ✅ Provide reusable query methods
- ❌ NOT for business logic
- ❌ NOT for validation

**Query Methods:**

```php
class EmployeeRepository extends ServiceEntityRepository {
    
    // Simple find by ID
    public function findById(int $id): ?Employee {
        return $this->find($id);
    }

    // WHERE clause
    public function findByStatus(string $status): array {
        return $this->findBy(['status' => $status]);
    }

    // Complex query with QueryBuilder
    public function findActiveByCompany(int $companyId): array {
        return $this->createQueryBuilder('e')
            ->where('e.companyId = :company')
            ->andWhere('e.status = :status')
            ->setParameter('company', $companyId)
            ->setParameter('status', 'active')
            ->orderBy('e.lastName', 'ASC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    // COUNT query
    public function countByCompany(int $companyId): int {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.companyId = :company')
            ->setParameter('company', $companyId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // JOIN query (across relationships)
    public function findWithCompany(): array {
        return $this->createQueryBuilder('e')
            ->join('e.company', 'c')
            ->addSelect('c')  // Load company data too
            ->getQuery()
            ->getResult();
    }
}
```

---

### 3. CONTROLLERS - Request Handlers

**Purpose:** Bridge HTTP and business logic.

**Responsibilities:**
- ✅ Receive HTTP requests
- ✅ Validate input
- ✅ Call repositories/services
- ✅ Return responses (HTML/JSON)
- ✅ Handle errors

**Typical Controller Flow:**

```php
#[Route('/employees', name: 'employee_')]
final class EmployeeController extends AbstractController {
    
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private EntityManagerInterface $entityManager,
        private FormFactoryInterface $formFactory
    ) {}

    // GET /employees - list view
    #[Route('', name: 'index')]
    public function index(Request $request): Response {
        // Get company context
        $company = $this->getUser()->getCompany();
        
        // Use repository to fetch data
        $employees = $this->employeeRepository->findByCompany($company->getId());
        
        // Return HTML response
        return $this->render('employee/index.html.twig', [
            'employees' => $employees,
        ]);
    }

    // GET /employees/{id} - detail view
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response {
        $employee = $this->employeeRepository->findById($id);
        
        if (!$employee) {
            throw $this->createNotFoundException('Employee not found');
        }
        
        return $this->render('employee/show.html.twig', [
            'employee' => $employee,
        ]);
    }

    // POST /employees - create
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response {
        $data = json_decode($request->getContent(), true);
        
        // Validate input
        if (!isset($data['firstName']) || empty($data['firstName'])) {
            return $this->json(['error' => 'First name required'], 400);
        }
        
        // Create entity
        $employee = new Employee();
        $employee->setFirstName($data['firstName']);
        $employee->setLastName($data['lastName']);
        $employee->setCompanyId($this->getUser()->getCompanyId());
        
        // Persist to database
        $this->entityManager->persist($employee);
        $this->entityManager->flush();
        
        // Return JSON response
        return $this->json([
            'success' => true,
            'id' => $employee->getId()
        ], 201);
    }

    // DELETE /employees/{id}
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): Response {
        $employee = $this->employeeRepository->findById($id);
        
        if (!$employee) {
            return $this->json(['error' => 'Not found'], 404);
        }
        
        $this->entityManager->remove($employee);
        $this->entityManager->flush();
        
        return $this->json(['success' => true]);
    }
}
```

**Request Methods (HTTP Verbs):**
- `GET` - Retrieve data (read-only, safe)
- `POST` - Create new resource
- `PUT/PATCH` - Update existing resource
- `DELETE` - Remove resource

---

## 🔐 Security Architecture

### 1. AUTHENTICATION (Who are you?)

**Purpose:** Verify user identity (login)

**Flow:**
```
1. User enters email + password in form
   ↓
2. LoginController receives request
   ↓
3. PasswordEncoder verifies password against database hash
   ↓
4. If correct → Create session/token
   ↓
5. Set user as authenticated
   ↓
6. Redirect to dashboard
```

**Code Example:**
```php
// src/Security/LoginController.php
#[Route('/login', name: 'login', methods: ['GET', 'POST'])]
public function login(
    LoginAuthenticator $authenticator,
    AuthenticationUtils $authUtils,
    Request $request
): Response {
    // If already logged in, go to dashboard
    if ($this->getUser()) {
        return $this->redirectToRoute('homeCompany');
    }

    // Get error if login failed
    $error = $authUtils->getLastAuthenticationError();
    $lastUsername = $authUtils->getLastUsername();

    return $this->render('login/index.html.twig', [
        'last_username' => $lastUsername,
        'error' => $error,
    ]);
}
```

**Key Concepts:**
- Users have credentials (email + password)
- Password is hashed using `argon2id` (secure algorithm)
- Never stored in plain text
- Session stores authenticated user

---

### 2. AUTHORIZATION (What can you do?)

**Purpose:** Control what actions users can perform

**Methods:**

#### A. Role-Based Access Control (RBAC)
```php
// Controller checks user has required role
#[Route('/admin', name: 'admin_dashboard')]
public function adminDashboard(): Response {
    // Deny access unless user has ROLE_ADMIN
    $this->denyAccessUnlessGranted('ROLE_ADMIN');
    
    return $this->render('admin/dashboard.html.twig');
}
```

**Common Roles:**
- `ROLE_ADMIN` - Full system access
- `ROLE_MANAGER` - Department/company access
- `ROLE_EMPLOYEE` - Own data access only
- `ROLE_CLIENT` - Limited client data access

#### B. Attribute-Based Access Control
```php
#[Route('/employees/{id}', name: 'employee_show')]
public function show(Employee $employee): Response {
    // Check if user is owner or has admin role
    $this->denyAccessUnlessGranted('VIEW', $employee);
    
    return $this->render('employee/show.html.twig', ['employee' => $employee]);
}
```

#### C. Security Voters (Custom Permission Logic)
```php
// src/Security/EmployeeVoter.php
class EmployeeVoter extends Voter {
    protected function supports(string $attribute, mixed $subject): bool {
        return in_array($attribute, ['VIEW', 'EDIT', 'DELETE'])
            && $subject instanceof Employee;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool {
        $user = $token->getUser();
        $employee = $subject;

        // Admin can do anything
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Managers can view their company employees
        if ($attribute === 'VIEW') {
            return $employee->getCompanyId() === $user->getCompanyId();
        }

        // Only the employee can edit themselves
        if ($attribute === 'EDIT') {
            return $employee->getId() === $user->getId();
        }

        return false;
    }
}
```

**Authorization Check Flow:**
```
1. Controller calls denyAccessUnlessGranted()
   ↓
2. Security system checks:
   a) Is user authenticated?
   b) Does user have required role?
   c) Does voter grant permission?
   ↓
3. If all pass → Allow request
   ↓
4. If any fail → Throw AccessDeniedException (403 Forbidden)
```

---

### 3. CURRENT SECURITY ISSUES (Why code is broken)

**CRITICAL ISSUE:** Many authorization checks are commented out:

```php
// ❌ WRONG - Authorization DISABLED
#[Route('/employees', name: 'index')]
public function index(): Response {
    // $this->denyAccessUnlessGranted('ROLE_MANAGER');  // COMMENTED OUT!
    
    // ANYONE can access this, even without login!
    return $this->render('employee/index.html.twig');
}

// ✅ CORRECT - Authorization ENABLED
#[Route('/employees', name: 'index')]
public function index(): Response {
    $this->denyAccessUnlessGranted('ROLE_MANAGER');
    
    // Only managers can see this
    return $this->render('employee/index.html.twig');
}
```

---

## 🎯 Common Patterns Used

### 1. Dependency Injection

**What:** Passing dependencies into objects instead of creating them internally.

```php
// ❌ BAD - Hard to test, tightly coupled
class EmployeeController {
    public function index() {
        $repo = new EmployeeRepository();  // Can't mock in tests
        $employees = $repo->findAll();
    }
}

// ✅ GOOD - Dependency injected
class EmployeeController {
    public function __construct(
        private EmployeeRepository $employeeRepository  // Injected
    ) {}
    
    public function index() {
        $employees = $this->employeeRepository->findAll();
    }
}
```

**Benefits:**
- Easy to test (inject mocks)
- Loosely coupled
- Flexible (can swap implementations)
- Symfony handles injection automatically

---

### 2. Entity Manager (Doctrine ORM)

**What:** Manages entity persistence (saving/loading from database)

```php
// Saving a new entity
$employee = new Employee();
$employee->setFirstName('John');

$entityManager->persist($employee);      // Register for insertion
$entityManager->flush();                 // Execute INSERT statement

// Updating an entity
$employee = $employeeRepository->find(1);
$employee->setFirstName('Jane');

$entityManager->flush();                 // Execute UPDATE statement

// Deleting an entity
$employeeRepository->remove($employee);
$entityManager->flush();                 // Execute DELETE statement
```

---

### 3. QueryBuilder (Fluent Query Interface)

**What:** Build SQL queries in PHP instead of raw SQL strings.

```php
// ❌ Raw SQL - prone to mistakes, SQL injection
$sql = "SELECT * FROM employee WHERE company_id = " . $_GET['company_id'];

// ✅ QueryBuilder - safe, readable
$employees = $this->createQueryBuilder('e')
    ->where('e.companyId = :company')
    ->setParameter('company', $companyId)
    ->orderBy('e.lastName', 'ASC')
    ->getQuery()
    ->getResult();
```

---

### 4. Event Listeners (Hooks)

**What:** Execute code automatically when certain events happen.

```php
// src/EventListener/EmployeeListener.php
#[AsEntityListener(event: 'postPersist', class: Employee::class)]
public function onEmployeeCreated(LifecycleEventArgs $args): void {
    $employee = $args->getObject();
    
    // Send welcome email
    $this->mailer->send(new EmployeeWelcomeEmail($employee));
}
```

---

### 5. Forms (Type-safe Input Handling)

**What:** Validates and transforms user input safely.

```php
// src/Form/EmployeeType.php
class EmployeeType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder
            ->add('firstName', TextType::class, [
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'required' => true,
            ])
            ->add('email', EmailType::class, [
                'required' => true,
            ]);
    }
}

// In controller
$form = $this->createForm(EmployeeType::class, $employee);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    // Data is validated and type-cast
    $this->entityManager->persist($employee);
    $this->entityManager->flush();
}
```

---

## 📁 Project Structure

```
web_project_symfony/
├── bin/
│   ├── console              # CLI tool for running commands
│   └── phpunit              # Testing executable
├── config/
│   ├── bundles.php          # Enabled Symfony bundles/extensions
│   ├── services.yaml        # Service configuration (dependency injection)
│   ├── routes.yaml          # Route definitions
│   └── packages/            # Configuration for each package
├── src/
│   ├── Controller/          # HTTP request handlers
│   │   ├── EmployeeController.php
│   │   ├── ClientController.php
│   │   └── ...
│   ├── Entity/              # Database ORM objects
│   │   ├── Employee.php
│   │   ├── Client.php
│   │   └── ...
│   ├── Repository/          # Data access objects
│   │   ├── EmployeeRepository.php
│   │   ├── ClientRepository.php
│   │   └── ...
│   ├── Form/                # Form types for input handling
│   ├── Security/            # Authentication/authorization
│   └── Kernel.php           # Application bootstrap
├── templates/               # Twig HTML templates
│   ├── base.html.twig       # Layout template
│   ├── employee/            # Employee-specific templates
│   ├── client/              # Client-specific templates
│   └── ...
├── public/
│   ├── index.php            # Entry point for web requests
│   ├── css/                 # Stylesheets
│   ├── js/                  # JavaScript files
│   └── uploads/             # User-uploaded files
├── migrations/              # Database schema versions
├── tests/                   # PHPUnit tests
├── vendor/                  # Third-party packages (Composer)
├── .env                     # Environment configuration
├── composer.json            # Project dependencies
└── compose.yaml             # Docker Compose config (database)
```

---

## 🔍 Visual Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER IN BROWSER                          │
└────────────────────┬────────────────────────────────────────────┘
                     │
                GET /clients
                     │
┌────────────────────▼────────────────────────────────────────────┐
│                    SYMFONY KERNEL                                │
│  ✓ Boot application                                              │
│  ✓ Load configuration                                            │
└────────────────────┬────────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────────┐
│                    ROUTING MATCHER                               │
│  Matches URL to controller action                               │
│  GET /clients → ClientController::index()                        │
└────────────────────┬────────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────────┐
│              DEPENDENCY INJECTION CONTAINER                      │
│  ✓ Create ClientController instance                              │
│  ✓ Inject dependencies (Repository, EntityManager, etc.)        │
└────────────────────┬────────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────────┐
│                   SECURITY CHECKS                                │
│  ✓ Check if user authenticated                                  │
│  ✓ Check if user has required role                              │
│  ✗ ISSUE: Checks are commented out!                             │
└────────────────────┬────────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────────┐
│              CONTROLLER ACTION EXECUTION                         │
│  ClientController::index()                                       │
└────────────────────┬────────────────────────────────────────────┘
                     │
        ┌────────────┴────────────┐
        │                         │
        ▼                         ▼
  ┌───────────┐          ┌──────────────┐
  │Repository │          │ EntityManager│
  │Query      │          │Persist/Flush │
  └─────┬─────┘          └──────┬───────┘
        │                       │
        │         ┌─────────────┘
        │         │
        ▼         ▼
    ┌──────────────────────┐
    │   DOCTRINE ORM       │
    │  Query Building      │
    │  SQL Generation      │
    └──────┬───────────────┘
           │
           ▼
    ┌──────────────────────┐
    │   MySQL/MariaDB      │
    │   Database           │
    │   SELECT * FROM      │
    │   client WHERE ...   │
    └──────┬───────────────┘
           │
    Database rows
           │
           ▼
    ┌──────────────────────┐
    │  DOCTRINE CONVERTS   │
    │  Rows → Objects      │
    │  [Client, Client,...]│
    └──────┬───────────────┘
           │
           ▼
    ┌──────────────────────┐
    │  TWIG TEMPLATE       │
    │  Renders HTML        │
    │  from entities       │
    └──────┬───────────────┘
           │
           ▼
    ┌──────────────────────┐
    │   HTML Response      │
    │   <table>...</table> │
    └──────┬───────────────┘
           │
           ▼
    ┌──────────────────────┐
    │   Browser Displays   │
    │   Client List Table  │
    └──────────────────────┘
```

---
