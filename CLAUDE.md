# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application with Filament 3.3 admin panel, designed as a CRM system for food service businesses. The application manages Organizations, Contacts, Interactions, Opportunities, and Distributors with a focus on Azure deployment and SQLite database.

[... existing content remains unchanged ...]

## Error Handling and Resilience Framework

### **1. Distinguish Error Types**

- **Protocol-Level Errors:**  
  - Use for critical issues like tool not found, permission denied, invalid parameters, or server exceptions unrelated to tool logic.
  - These are typically thrown as exceptions or JSON-RPC error responses and are not visible to the LLM for recovery[1][2][3].

- **Tool Execution Errors:**  
  - Use structured error responses (with `isError: true`) for logic errors, invalid values, failed operations, or any error the LLM should see and handle.
  - Return these as part of the tool's normal response, allowing AI agents to reason, retry, or adapt strategy[1][2][4].

---

### **2. Standardize Error Responses**

- Use a consistent structure for error results:
  - Set `isError: true` in the result object.
  - Include a clear, human-readable error message in the `content` array.
  - Provide error codes and, where appropriate, detailed metadata (timestamp, context, etc.)[2][3][4].
- For JSON-RPC, use well-defined error codes and messages, and avoid exposing sensitive internal details[5][3].

**Example (Python-like):**
```python
try:
    result = perform_operation()
    return types.CallToolResult(content=[types.TextContent(type="text", text=f"Success: {result}")])
except Exception as error:
    return types.CallToolResult(
        isError=True,
        content=[types.TextContent(type="text", text=f"Error: {str(error)}")]
    )
```

---

### **3. Logging and Monitoring**

- Log all errors with:
  - Timestamp
  - Severity level
  - Error description
  - Contextual data (user/session, operation, etc.)
  - Suggested resolution if possible[5][3].
- Use centralized, real-time dashboards for error tracking and alerting[5][3].
- Ensure logs are secure and compliant with privacy requirements[5][3][4].

---

### **4. Error Prevention and Recovery Strategies**

- **Input Validation:**  
  - Validate all parameters against schemas before processing.
  - Sanitize file paths, URLs, and commands to prevent injection or misuse[2][4].

- **Timeouts and Retries:**  
  - Implement timeouts for long-running operations.
  - Use exponential backoff and retry strategies for transient errors[3].

- **Resource Management:**  
  - Clean up resources after errors (files, memory, connections)[2][3].
  - Monitor for resource allocation failures and handle gracefully[3].

- **Access Control:**  
  - Enforce authentication and authorization checks.
  - Log and audit access attempts and failures[2][4].

---

### **5. Human-in-the-Loop and Diagnostics**

- For critical or ambiguous errors, escalate to human oversight.
- Provide detailed diagnostics in error responses to facilitate troubleshooting and recovery[4].

---

### **6. Continuous Improvement**

- Analyze error patterns regularly to refine error handling strategies.
- Use predictive analytics and machine learning to anticipate and prevent recurring issues[5][3].

---

### **Summary Table**

| Rule Area              | Key Practices                                              |
|------------------------|-----------------------------------------------------------|
| Error Type             | Distinguish protocol vs. tool execution errors             |
| Standardization        | Use `isError: true`, clear messages, codes, and metadata   |
| Logging/Monitoring     | Centralized, secure, contextual, actionable logs           |
| Prevention/Recovery    | Validate inputs, use timeouts/retries, manage resources    |
| Access Control         | Authenticate, authorize, audit                             |
| Human Oversight        | Escalate critical errors, provide diagnostics              |
| Continuous Improvement | Analyze, predict, and adapt error handling                 |

## Rules for Handling Errors with MCP Tools

Below is a structured set of practical, field-tested rules for error handling with MCP (Model Context Protocol) tools, based on current best practices and standards:

---

### **1. Distinguish Error Types**

- **Protocol-Level Errors:**  
  - Use for critical issues like tool not found, permission denied, invalid parameters, or server exceptions unrelated to tool logic.
  - These are typically thrown as exceptions or JSON-RPC error responses and are not visible to the LLM for recovery.

- **Tool Execution Errors:**  
  - Use structured error responses (with `isError: true`) for logic errors, invalid values, failed operations, or any error then learning to anticipate and prevent recurring issues.
  - Return these as part of the tool's normal response, allowing AI agents to reason, retry, or adapt strategy.

---

### **Summary Table**

| Rule Area              | Key Practices                                              |
|------------------------|------------------------------------------------------------|
| Error Type             | Distinguish protocol vs. tool execution errors             |
| Standardization        | Use `isError: true`, clear messages, codes, and metadata   |
| Logging/Monitoring     | Centralized, secure, contextual, actionable logs           |
| Prevention/Recovery    | Validate inputs, use timeouts/retries, manage resources    |
| Access Control         | Authenticate, authorize, audit                             |
| Human Oversight        | Escalate critical errors, provide diagnostics              |
| Continuous Improvement | Analyze, predict, and adapt error handling                 |

---

### **2. Standardize Error Responses**

- Use a consistent structure for error results:
  - Set `isError: true` in the result object.
  - Include a clear, human-readable error message in the `content` array.
  - Provide error codes and, where appropriate, detailed metadata (timestamp, context, etc.).
- For JSON-RPC, use well-defined error codes and messages, and avoid exposing sensitive internal details.

**Example (Python-like):**
```python
try:
    result = perform_operation()
    return types.CallToolResult(content=[types.TextContent(type="text", text=f"Success: {result}")])
except Exception as error:
    return types.CallToolResult(
        isError=True,
        content=[types.TextContent(type="text", text=f"Error: {str(error)}")]
    )
```

---

### **3. Logging and Monitoring**

- Log all errors with:
  - Timestamp
  - Severity level
  - Error description
  - Contextual data (user/session, operation, etc.)
  - Suggested resolution if possible.
- Use centralized, real-time dashboards for error tracking and alerting.
- Ensure logs are secure and compliant with privacy requirements.

---

### **4. Error Prevention and Recovery Strategies**

- **Input Validation:**  
  - Validate all parameters against schemas before processing.
  - Sanitize file paths, URLs, and commands to prevent injection or misuse.

- **Timeouts and Retries:**  
  - Implement timeouts for long-running operations.
  - Use exponential backoff and retry strategies for transient errors.

- **Resource Management:**  
  - Clean up resources after errors (files, memory, connections).
  - Monitor for resource allocation failures and handle gracefully.

- **Access Control:**  
  - Enforce authentication and authorization checks.
  - Log and audit access attempts and failures.

---

### **5. Human-in-the-Loop and Diagnostics**

- For critical or ambiguous errors, escalate to human oversight.
- Provide detailed diagnostics in error responses to facilitate troubleshooting and recovery.

---

### **6. Continuous Improvement**

- Analyze error patterns regularly to refine error handling strategies.
- Use predictive analytics and machi

[... rest of existing content remains unchanged ...]

## Rule: Never Use `[..., rest of existing content remains unchanged ...]`

### **Purpose**
- Prevents ambiguous, incomplete, or placeholder text in code, documentation, or tool outputs.
- Ensures all content is explicit, actionable, and production-ready.

---

### **Rule Statement**
- **Never include** the phrase `[..., rest of existing content remains unchanged ...]` in:
  - Code comments
  - Documentation
  - Tool outputs
  - Pull request descriptions
  - Memory or guideline files

---

### **Best Practices**
- **Always provide full, explicit content**—do not rely on placeholders or ellipses for omitted sections.
- **If content is unchanged:**  
  - State explicitly: "No changes to the following sections."
  - Or, omit unchanged sections entirely and focus on what's updated.
- **For code or doc updates:**  
  - Show only the relevant diffs or the complete, updated block.
  - Avoid using ellipses or generic placeholders.

---

### **Practical Examples**

**❌ Incorrect:**
```markdown
def update_user():
    # ... rest of existing content remains unchanged ...
```

**✅ Correct:**
```markdown
def update_user():
    # No changes to the rest of the function.
    # (Or, show the full function if context is required)
```

**❌ Incorrect in PR:**
> Updated header. [... rest of existing content remains unchanged ...]

**✅ Correct in PR:**
> Updated header. No other sections were modified.

---

### **Enforcement**
- Add this rule to your CLAUDE.md, project guidelines, or code review checklist.
- Reject or request changes for any PR, commit, or doc containing this placeholder.

---

### **Summary Table**

| Context        | What to Do Instead                         |
|----------------|--------------------------------------------|
| Code           | Show full code or state "No changes"       |
| Docs           | Omit unchanged sections                    |
| PRs/Commits    | Explicitly mention unchanged areas         |
| Tool Outputs   | Provide complete, actionable information   |