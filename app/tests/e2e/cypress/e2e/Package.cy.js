describe('Package page', () => {
  it('shows package details', () => {
    cy.visit('/packages/core/x86_64/pacman', { waitForApi: true })
    cy.get('h1').contains('pacman')
  })

  it('shows dependencies', () => {
    cy.visit('/packages/core/x86_64/pacman', { waitForApi: true })
    cy.get('[data-test=package-relations-dependency] li').should('have.length.gt', 3)
    cy.get('[data-test=package-relations-dependency] li a').should('have.attr', 'href')
  })

  it('navigates to dependency', () => {
    cy.visit('/packages/core/x86_64/pacman', { waitForApi: true })
    cy.get('[data-test=package-relations-dependency] a').first().click()
    cy.location().should((loc) => {
      expect(loc.pathname).to.not.eq('/packages/core/x86_64/pacman')
    })
    cy.get('h1').should('not.contain', 'pacman')
  })

  it('shows files', () => {
    cy.visit('/packages/core/x86_64/pacman', { waitForApi: true })
    cy.get('[data-test=package-files]').should('not.contain', 'usr/')
    cy.get('[data-test=package-show-files]').click()
    cy.get('[data-test=package-files]').should('contain', 'usr/')
  })

  it('shows suggestions', () => {
    cy.visit('/packages/core/x86_64/pac', { waitForApi: true })
    cy.get('main').contains('pacman')
  })
})
