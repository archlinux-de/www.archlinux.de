describe('Impressum page', () => {
  beforeEach(() => {
    cy.visit('/impressum')
  })

  it('shows name', () => {
    cy.contains('main', 'Pierre Schmitz')
  })

  it('shows address', () => {
    cy.contains('main', 'Bonn')
  })

  it('footer links to Impressum', () => {
    cy.get('footer a[href*=impressum]').should('be.visible')
  })
})
