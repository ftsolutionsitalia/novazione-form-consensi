# CLAUDE.md - AI Assistant Guidelines

## Project Overview

**Project Name:** novazione-form-consensi
**Purpose:** Consent forms management application ("form consensi" in Italian)
**Status:** Initial development phase - repository freshly initialized

This project is intended to handle consent form workflows, likely for managing user consent collection, storage, and compliance.

## Current Repository State

This is a newly initialized repository containing only:
- `README.md` - Basic project description
- `CLAUDE.md` - This file (AI assistant guidelines)

The project requires scaffolding and initial setup before feature development can begin.

## Development Guidelines

### Technology Stack (To Be Established)

When setting up this project, consider:
- **Frontend Framework:** React, Vue, or similar for form UI
- **Language:** TypeScript preferred for type safety
- **Build Tool:** Vite recommended for modern development
- **Styling:** Tailwind CSS or styled-components
- **Form Handling:** React Hook Form, Formik, or similar
- **Testing:** Vitest or Jest for unit/integration tests

### Project Structure (Recommended)

```
novazione-form-consensi/
├── src/
│   ├── components/      # Reusable UI components
│   ├── pages/           # Page-level components
│   ├── forms/           # Form definitions and schemas
│   ├── hooks/           # Custom React hooks
│   ├── services/        # API and business logic
│   ├── types/           # TypeScript type definitions
│   ├── utils/           # Utility functions
│   └── styles/          # Global styles
├── public/              # Static assets
├── tests/               # Test files
├── docs/                # Documentation
└── config files...
```

### Code Conventions

1. **File Naming:**
   - Components: PascalCase (e.g., `ConsentForm.tsx`)
   - Utilities: camelCase (e.g., `validateConsent.ts`)
   - Types: PascalCase with descriptive names (e.g., `ConsentFormData.ts`)

2. **Code Style:**
   - Use TypeScript strict mode
   - Prefer functional components with hooks
   - Keep components small and focused
   - Extract reusable logic into custom hooks

3. **Documentation:**
   - Document complex business logic
   - Add JSDoc comments for public APIs
   - Keep README.md updated with setup instructions

### Git Workflow

1. **Branch Naming:**
   - Features: `feature/description`
   - Bugs: `fix/description`
   - AI work: `claude/session-id`

2. **Commit Messages:**
   - Use conventional commits format
   - Examples:
     - `feat: add consent form component`
     - `fix: correct validation logic`
     - `docs: update README with setup instructions`
     - `chore: configure build tools`

3. **Pull Requests:**
   - Include clear description of changes
   - Reference related issues
   - Ensure tests pass before merging

## AI Assistant Instructions

### When Working on This Project

1. **Before Making Changes:**
   - Read existing code to understand patterns
   - Check for established conventions
   - Review recent commits for context

2. **When Adding Features:**
   - Follow existing code patterns
   - Add appropriate types for new code
   - Consider form validation requirements
   - Think about accessibility (a11y)

3. **For Consent Forms Specifically:**
   - Ensure forms are accessible (WCAG compliance)
   - Handle consent data securely
   - Consider GDPR and privacy requirements
   - Implement proper validation
   - Provide clear user feedback

4. **Testing:**
   - Add tests for new functionality
   - Test form validation edge cases
   - Consider accessibility testing

### Common Tasks

**Initial Project Setup:**
```bash
# Initialize npm project
npm init -y

# Install core dependencies (example for React + Vite)
npm create vite@latest . -- --template react-ts

# Install form handling
npm install react-hook-form zod @hookform/resolvers

# Install dev dependencies
npm install -D vitest @testing-library/react
```

**Running the Project (once set up):**
```bash
npm install        # Install dependencies
npm run dev        # Start development server
npm run build      # Production build
npm run test       # Run tests
npm run lint       # Check code style
```

## Security Considerations

For consent form applications:
- Never log sensitive consent data
- Implement proper input sanitization
- Use HTTPS for all data transmission
- Consider data retention policies
- Implement audit logging for consent changes
- Handle PII (Personally Identifiable Information) with care

## Notes for Future Updates

As the project evolves, update this file with:
- Specific technology choices made
- API endpoints and data models
- Environment configuration requirements
- Deployment procedures
- Team-specific conventions
