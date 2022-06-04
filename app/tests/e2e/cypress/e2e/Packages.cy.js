describe('Packages page', () => {
  beforeEach(() => {
    cy.visit('/packages')
  })

  it('shows packages', () => {
    cy.get('[data-test=packages] tr').should('have.length.gt', 20)
    cy.get('[data-test=packages] a').should('have.attr', 'href')
  })

  it('filter packages', () => {
    cy.get('[data-test=packages-search]').type('pacman')
    cy.get('[data-test=packages] a').contains('pacman')
    cy.get('[data-test=packages] tr').should('have.length.lt', 20)
  })
})
