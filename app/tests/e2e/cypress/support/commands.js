Cypress.Commands.overwrite('visit', (originalFn, url, options = { waitForApi: false }) => {
  if (options.waitForApi) {
    cy.intercept({ pathname: /^\/api\//, middleware: true }, req => { req.continue(res => { delete res.headers['cache-control'] }) }).as('api')
  }

  originalFn(url, options)

  if (options.waitForApi) {
    cy.wait('@api')
  }

  cy.get('#content').should('be.visible')
})
