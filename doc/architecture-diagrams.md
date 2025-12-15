# Architecture Diagrams

## System Context Diagram

```mermaid
flowchart TD
    User((End User))
    Admin((Administrator))

    subgraph "Host Application"
        SA[SwiftAuth Package]
    end

    DB[(Database)]
    Cache[(Cache / Redis)]
    Email[BirdFlock / SMTP]

    User -->|Login / Register| SA
    Admin -->|Manage Users| SA
    
    SA -->|Reads/Writes Users & Sessions| DB
    SA -->|Rate Limiting & Locks| Cache
    SA -->|Sends Notifications| Email
```

## Container Diagram (Package Integration)

```mermaid
flowchart TD
    subgraph "Host Laravel App"
        Route[Route Service Provider]
        Config[config/swift-auth.php]
        
        subgraph "SwiftAuth Package"
            SP[SwiftAuthServiceProvider]
            Controllers[Http Controllers]
            Services[Auth Services]
            Models[Eloquent Models]
            Cmds[Artisan Commands]
        end
    end
    
    Route -->|Loads| Controllers
    SP -->|Registers| Services
    SP -->|Publishes| Config
    
    Controllers -->|Uses| Services
    Cmds -->|Uses| Services
    Services -->|Uses| Models
```

## Component Diagram (Core Auth Flow)

```mermaid
classDiagram
    class AuthController {
        +login(Request)
        +logout(Request)
    }

    class SwiftSessionAuth {
        +attempt(credentials)
        +login(User)
        +logout()
    }

    class UserRepository {
        +findByEmail(email)
        +validateCredentials(user, password)
    }

    class MfaService {
        +requiresMfa(user)
        +generateChallenge(user)
    }

    AuthController --> SwiftSessionAuth
    SwiftSessionAuth --> UserRepository
    SwiftSessionAuth --> MfaService
```
