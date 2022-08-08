Cypress.Commands.overwrite('visit', (originalFn, url, options) => {
  originalFn(url, options)
  // @FIXME: Find a way to detect if data fetching is finished
  // eslint-disable-next-line cypress/no-unnecessary-waiting
  return cy.wait(500)
})
