describe('Privacy Policy page', () => {
  beforeEach(() => {
    cy.visit('/privacy-policy')
  })

  it('shows title', () => {
    cy.contains('h1', 'Datenschutz')
  })

  it('mentions user account', () => {
    cy.contains('main', 'Benutzerkonto')
  })

  it('footer links to Privacy Policy', () => {
    cy.get('footer a[href*=privacy-policy]').should('be.visible')
  })
})
