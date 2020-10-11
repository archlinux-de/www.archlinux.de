describe('Package page', () => {
  beforeEach(() => {
    cy.visit('/packages/core/x86_64/pacman')
  })

  it('shows package details', () => {
    cy.get('h1').contains('pacman')
  })

  it('shows dependencies', () => {
    cy.get('[data-test=package-relations-dependency] li').should('have.length.gt', 3)
    cy.get('[data-test=package-relations-dependency] li a').should('have.attr', 'href')
  })
})
