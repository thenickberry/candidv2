# Framework Evaluation

This document evaluates whether CANDIDv2 should adopt a micro-framework or continue with its current frameworkless architecture.

## Current Architecture

The application currently uses a custom frameworkless architecture consisting of:

| Component | Lines | Description |
|-----------|-------|-------------|
| Router | ~160 | Pattern-based routing with parameter extraction |
| Container | ~116 | Simple DI container with lazy instantiation |
| Controllers | ~1200 | 9 controllers extending a base Controller class |
| Services | ~1100 | Database, Auth, Template, ImageStorage, etc. |
| **Total** | **~3800** | Complete application code |

### Current Strengths

1. **No external dependencies** (except phpdotenv)
2. **Full control** over every component
3. **Simple mental model** — easy to trace request flow
4. **Fast** — no framework overhead
5. **Small footprint** — minimal code to maintain

### Current Limitations

1. **No middleware support** — CSRF, auth checks done manually in controllers
2. **Basic error handling** — custom HttpException, but no stack traces in dev
3. **No route caching** — routes compiled on every request
4. **Limited HTTP abstractions** — direct use of $_GET, $_POST, $_SERVER

---

## Framework Candidates

### 1. Slim 4

**Overview:** Slim is a PHP micro-framework for building APIs and web applications. Version 4 is PSR-7/PSR-15 compliant.

**Pros:**
- PSR-7 HTTP message interfaces (Request/Response objects)
- PSR-15 middleware support (clean cross-cutting concerns)
- PSR-11 container support (use any DI container)
- Route groups and named routes
- Mature ecosystem with good documentation
- Active maintenance and security updates

**Cons:**
- Requires 5+ additional packages (PSR implementations, HTTP factory, etc.)
- PSR-7 immutability can be verbose (`$response = $response->withHeader(...)`)
- Learning curve for PSR-7/PSR-15 patterns
- Overkill for simple CRUD applications

**Dependencies Added:**
```json
{
    "slim/slim": "^4.12",
    "slim/psr7": "^1.6",
    "php-di/php-di": "^7.0"
}
```

**Migration Effort:** Medium-High
- Rewrite all controllers to use PSR-7 Request/Response
- Convert auth/CSRF checks to middleware
- Update all `redirect()` and `echo` calls to return Response objects

---

### 2. Flight

**Overview:** Flight is an extensible micro-framework for PHP, focused on simplicity.

**Pros:**
- Very lightweight (~2000 lines total)
- Simple, intuitive API
- No PSR dependencies required
- Easy migration from procedural code
- Built-in routing, views, and error handling

**Cons:**
- Less active development than Slim
- Non-PSR compliant (harder to swap components)
- Global/static API style (`Flight::route()`, `Flight::render()`)
- Smaller community and ecosystem
- No native middleware support (added in v3)

**Dependencies Added:**
```json
{
    "mikecao/flight": "^2.0"
}
```

**Migration Effort:** Low-Medium
- Simpler API closer to current code style
- Can gradually migrate controllers
- Less boilerplate than Slim

---

### 3. Frameworkless (Current)

**Overview:** Continue with the custom Router/Container architecture.

**Pros:**
- Already implemented and working
- Zero additional dependencies
- Complete understanding of codebase
- Fastest possible execution
- No framework deprecations or breaking changes to track

**Cons:**
- Must implement missing features manually (middleware, etc.)
- No community support for core components
- Potential security gaps if not carefully maintained
- Reinventing patterns that frameworks have solved

**Enhancement Options:**
1. Add simple middleware support to Router (~50 lines)
2. Add route caching for production (~30 lines)
3. Add PSR-7 Request/Response wrappers if needed later

---

## Comparison Matrix

| Criteria | Slim 4 | Flight | Frameworkless |
|----------|--------|--------|---------------|
| Learning Curve | Medium | Low | None |
| Migration Effort | High | Medium | None |
| Dependencies | 5-8 packages | 1 package | 0 packages |
| Middleware Support | Excellent | Basic (v3) | None (can add) |
| PSR Compliance | Full | None | None |
| Performance | Good | Better | Best |
| Long-term Maintenance | Community | Community | Self |
| Flexibility | High | Medium | Highest |

---

## Recommendation

**Continue with the frameworkless approach**, with targeted enhancements.

### Rationale

1. **The application is already working.** The custom Router and Container handle all current requirements. Migrating to a framework would be effort spent rewriting working code.

2. **Scope is limited.** CANDIDv2 is an image management system, not a platform that will grow into dozens of modules. The current architecture is appropriate for the application's scope.

3. **Dependencies should be minimized.** Per project guidelines, we prefer native solutions. The current approach has only one runtime dependency (phpdotenv).

4. **Migration cost exceeds benefit.** Slim 4 would require rewriting every controller to use PSR-7. Flight would require less work but adds a dependency for minimal gain.

5. **Enhancements are trivial to add.** If middleware becomes necessary, it can be added to the custom Router in ~50 lines without adopting a full framework.

### Suggested Enhancements (Optional)

If specific framework features are needed later:

1. **Middleware Support**
   ```php
   // Add to Router
   public function middleware(callable $middleware): self
   ```

2. **Route Groups**
   ```php
   $router->group('/admin', function($router) {
       $router->get('/users', 'AdminController', 'users');
   })->middleware($adminAuth);
   ```

3. **Named Routes**
   ```php
   $router->get('/image/{id}', 'ImageController', 'detail')->name('image.detail');
   url('image.detail', ['id' => 5]); // Returns /image/5
   ```

These can be implemented incrementally as needed, without committing to a full framework migration.

---

## Conclusion

The frameworkless approach is the right choice for CANDIDv2. The application's requirements are well-served by the current architecture, and the cost of migrating to a framework outweighs the benefits. If framework-like features become necessary, they can be added incrementally to the existing codebase.
